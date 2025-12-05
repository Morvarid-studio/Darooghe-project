<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CompanyAccountController extends Controller
{
    /**
     * دریافت لیست حساب‌ها برای ثبت تراکنش (با فیلتر role-based)
     */
    public function getAccountsForTransaction(Request $request)
    {
        $user = Auth::user();
        
        if ($user->isAdmin()) {
            // Admin: همه حساب‌های فعال
            $accounts = Account::with(['tags', 'user'])
                ->where('is_active', true)
                ->orderBy('account_type')
                ->orderBy('display_name')
                ->get();
        } else {
            // کاربر عادی: فقط حساب‌هایی که role او در allowed_roles است + حساب خودش
            $accounts = Account::with(['tags', 'user'])
                ->where('is_active', true)
            ->where(function($q) use ($user) {
                $q->where('user_id', $user->id) // حساب خودش
                  ->orWhereExists(function($query) use ($user) {
                      $query->select(\Illuminate\Support\Facades\DB::raw(1))
                          ->from('account_role')
                          ->whereColumn('account_role.account_id', 'accounts.id')
                          ->where('account_role.role', $user->role);
                  });
            })
                ->orderBy('account_type')
                ->orderBy('display_name')
                ->get();
        }
        
        return response()->json($accounts);
    }

    /**
     * دریافت تراکنش‌های حساب اصلی شرکت
     */
    public function getCompanyTransactions(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز. فقط مدیران می‌توانند به این بخش دسترسی داشته باشند.'
            ], 403);
        }

        // پیدا کردن حساب اصلی شرکت
        $companyAccount = Account::where('account_type', 'company')
            ->where('is_active', true)
            ->first();

        if (!$companyAccount) {
            return response()->json([
                'message' => 'حساب اصلی شرکت یافت نشد.'
            ], 404);
        }

        // دریافت تراکنش‌هایی که از حساب اصلی خرج شده یا به حساب اصلی وارد شده
        $transactions = Transaction::with(['fromAccount', 'toAccount', 'user'])
            ->where(function($q) use ($companyAccount) {
                $q->where('from_account_id', $companyAccount->id)
                  ->orWhere('to_account_id', $companyAccount->id);
            })
            ->active()
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // تبدیل تاریخ‌های میلادی به شمسی و اضافه کردن type
        $transactions->transform(function ($transaction) use ($companyAccount) {
            // تبدیل payment_date به string شمسی (فقط تاریخ بدون timestamp)
            $paymentDate = $transaction->payment_date;
            if ($paymentDate instanceof \Carbon\Carbon) {
                $paymentDate = $paymentDate->format('Y-m-d');
            }
            $transaction->payment_date = DateHelper::miladiToShamsi($paymentDate, 'Y/m/d');
            
            // تبدیل created_at به string شمسی (فقط تاریخ بدون timestamp)
            $createdAt = $transaction->created_at;
            if ($createdAt instanceof \Carbon\Carbon) {
                $createdAt = $createdAt->format('Y-m-d');
            }
            $transaction->created_at_shamsi = DateHelper::miladiToShamsi($createdAt, 'Y/m/d');
            
            // محاسبه type به صورت خودکار
            if ($transaction->from_account_id == $companyAccount->id) {
                $transaction->type = 'cost';
            } elseif ($transaction->to_account_id == $companyAccount->id) {
                $transaction->type = 'receive';
            } else {
                $fromAccount = $transaction->fromAccount;
                if ($fromAccount && $fromAccount->account_type === 'company') {
                    $transaction->type = 'cost';
                } else {
                    $transaction->type = 'receive';
                }
            }
            
            return $transaction;
        });

        return response()->json([
            'company_account' => $companyAccount,
            'transactions' => $transactions
        ]);
    }

    /**
     * ثبت تراکنش برای حساب اصلی شرکت
     */
    public function storeTransaction(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز. فقط مدیران می‌توانند تراکنش ثبت کنند.'
            ], 403);
        }

        $validated = $request->validate([
            'payment_date' => 'required|string', // دریافت به صورت شمسی
            'amount_decimal' => 'required|numeric|min:0',
            'category' => 'required|string',
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id',
            'description' => 'nullable|string',
            'invoice' => 'file|mimes:pdf,doc,docx|max:2048|nullable',
        ]);

        // اعتبارسنجی و تبدیل تاریخ شمسی به میلادی
        if (!DateHelper::isValidShamsiDate($validated['payment_date'])) {
            return response()->json([
                'message' => 'تاریخ پرداخت نامعتبر است. فرمت صحیح: Y/m/d (مثلاً 1403/07/15)'
            ], 422);
        }

        // تبدیل تاریخ شمسی به میلادی برای ذخیره در دیتابیس
        $validated['payment_date'] = DateHelper::shamsiToMiladi($validated['payment_date']);

        $validated['user_id'] = $user->id; // ثبت‌کننده
        $validated['handled_by'] = $user->user_name;
        $validated['archived'] = false;

        $validated['invoice'] = null;
        if ($request->hasFile('invoice')) {
            $validated['invoice'] = $request->file('invoice')->store('invoices', 'public');
        }

        $transaction = Transaction::create($validated);

        // پیدا کردن حساب اصلی شرکت برای محاسبه type
        $companyAccount = Account::where('account_type', 'company')
            ->where('is_active', true)
            ->first();

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت (فقط تاریخ بدون timestamp)
        $paymentDate = $transaction->payment_date;
        if ($paymentDate instanceof \Carbon\Carbon) {
            $paymentDate = $paymentDate->format('Y-m-d');
        }
        $transaction->payment_date = DateHelper::miladiToShamsi($paymentDate, 'Y/m/d');
        
        $createdAt = $transaction->created_at;
        if ($createdAt instanceof \Carbon\Carbon) {
            $createdAt = $createdAt->format('Y-m-d');
        }
        $transaction->created_at_shamsi = DateHelper::miladiToShamsi($createdAt, 'Y/m/d');
        
        $transaction->load(['fromAccount', 'toAccount', 'user']);
        
        // محاسبه type به صورت خودکار
        if ($companyAccount) {
            if ($transaction->from_account_id == $companyAccount->id) {
                $transaction->type = 'cost';
            } elseif ($transaction->to_account_id == $companyAccount->id) {
                $transaction->type = 'receive';
            } else {
                $fromAccount = $transaction->fromAccount;
                if ($fromAccount && $fromAccount->account_type === 'company') {
                    $transaction->type = 'cost';
                } else {
                    $transaction->type = 'receive';
                }
            }
        }

        return response()->json([
            'message' => 'تراکنش با موفقیت ثبت شد.',
            'data' => $transaction
        ], 201);
    }

    /**
     * دریافت موجودی حساب اصلی شرکت
     */
    public function getCompanyBalance(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز.'
            ], 403);
        }

        $companyAccount = Account::where('account_type', 'company')
            ->where('is_active', true)
            ->first();

        if (!$companyAccount) {
            return response()->json([
                'message' => 'حساب اصلی شرکت یافت نشد.'
            ], 404);
        }

        // محاسبه موجودی از تراکنش‌ها
        $totalReceived = Transaction::where('to_account_id', $companyAccount->id)
            ->where('archived', false)
            ->sum('amount_decimal');

        $totalCosts = Transaction::where('from_account_id', $companyAccount->id)
            ->where('archived', false)
            ->sum('amount_decimal');

        $balance = $totalReceived - $totalCosts;

        // محاسبه موجودی تنخواه گردان (جمع موجودی تمام حساب‌های تنخواه)
        $pettyCashAccounts = Account::where('account_type', 'petty_cash')
            ->where('is_active', true)
            ->get();

        $pettyCashBalance = 0;
        foreach ($pettyCashAccounts as $pettyCashAccount) {
            $pettyCashReceived = Transaction::where('to_account_id', $pettyCashAccount->id)
                ->where('archived', false)
                ->sum('amount_decimal');
            
            $pettyCashCosts = Transaction::where('from_account_id', $pettyCashAccount->id)
                ->where('archived', false)
                ->sum('amount_decimal');
            
            $pettyCashBalance += ($pettyCashReceived - $pettyCashCosts);
        }

        return response()->json([
            'company_account' => $companyAccount,
            'total_received' => $totalReceived,
            'total_costs' => $totalCosts,
            'balance' => $balance,
            'petty_cash_balance' => $pettyCashBalance // موجودی تنخواه گردان
        ]);
    }

    /**
     * آرشیو کردن تراکنش حساب اصلی شرکت
     */
    public function archiveTransaction(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز. فقط مدیران می‌توانند تراکنش آرشیو کنند.'
            ], 403);
        }

        // پیدا کردن حساب اصلی شرکت
        $companyAccount = Account::where('account_type', 'company')
            ->where('is_active', true)
            ->first();

        if (!$companyAccount) {
            return response()->json([
                'message' => 'حساب اصلی شرکت یافت نشد.'
            ], 404);
        }

        // پیدا کردن تراکنش که مربوط به حساب شرکت باشد
        $transaction = Transaction::where('id', $id)
            ->where(function($q) use ($companyAccount) {
                $q->where('from_account_id', $companyAccount->id)
                  ->orWhere('to_account_id', $companyAccount->id);
            })
            ->firstOrFail();

        $transaction->archived = true;
        $transaction->save();
        $transaction->load(['fromAccount', 'toAccount', 'user']);

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت (فقط تاریخ بدون timestamp)
        $paymentDate = $transaction->payment_date;
        if ($paymentDate instanceof \Carbon\Carbon) {
            $paymentDate = $paymentDate->format('Y-m-d');
        }
        $transaction->payment_date = DateHelper::miladiToShamsi($paymentDate, 'Y/m/d');
        
        $createdAt = $transaction->created_at;
        if ($createdAt instanceof \Carbon\Carbon) {
            $createdAt = $createdAt->format('Y-m-d');
        }
        $transaction->created_at_shamsi = DateHelper::miladiToShamsi($createdAt, 'Y/m/d');

        // محاسبه type به صورت خودکار
        if ($transaction->from_account_id == $companyAccount->id) {
            $transaction->type = 'cost';
        } elseif ($transaction->to_account_id == $companyAccount->id) {
            $transaction->type = 'receive';
        } else {
            $fromAccount = $transaction->fromAccount;
            if ($fromAccount && $fromAccount->account_type === 'company') {
                $transaction->type = 'cost';
            } else {
                $transaction->type = 'receive';
            }
        }

        return response()->json([
            'message' => 'تراکنش با موفقیت آرشیو شد.',
            'data' => $transaction
        ]);
    }
}

