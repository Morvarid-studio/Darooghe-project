<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_date' => 'required|string', // دریافت به صورت شمسی
            'amount_decimal' => 'required|numeric|min:0',
            'category' => 'required|string',
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id',
            'description' => 'nullable|string',
            'invoice' => 'file|mimes:pdf,doc,docx|max:2048|nullable',
            'archived' => 'boolean',
        ]);

        // اعتبارسنجی و تبدیل تاریخ شمسی به میلادی
        if (!DateHelper::isValidShamsiDate($validated['payment_date'])) {
            return response()->json([
                'message' => 'تاریخ پرداخت نامعتبر است. فرمت صحیح: Y/m/d (مثلاً 1403/07/15)'
            ], 422);
        }

        // تبدیل تاریخ شمسی به میلادی برای ذخیره در دیتابیس
        $validated['payment_date'] = DateHelper::shamsiToMiladi($validated['payment_date']);

        $validated['archived'] = $request->input('archived', false);
        $validated['user_id'] = auth()->id(); // ثبت‌کننده
        $validated['handled_by'] = auth()->user()->user_name;

        $validated['invoice'] = null;
        if ($request->hasFile('invoice')) {
            $validated['invoice'] = $request->file('invoice')->store('invoices', 'public');
        }

        $transaction = Transaction::create($validated);
        $transaction->load(['fromAccount', 'toAccount', 'user']);

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
        } else {
            $fromAccount = $transaction->fromAccount;
            if ($fromAccount && $fromAccount->account_type === 'company') {
                $transaction->type = 'cost';
            } else {
                $transaction->type = 'receive';
            }
        }

        return response()->json([
            'message' => 'اطلاعات با موفقیت ثبت شد.',
            'data' => $transaction
        ], 201);
    }

    public function show()
    {
        $user = Auth::user();

        // تراکنش‌هایی که کاربر ثبت کرده یا تراکنش‌هایی که مربوط به حساب‌های اوست
        $transactions = Transaction::with(['fromAccount', 'toAccount', 'user'])
            ->where(function($q) use ($user) {
                $q->where('user_id', $user->id) // تراکنش‌هایی که خودش ثبت کرده
                  ->orWhereHas('fromAccount', function($query) use ($user) {
                      $query->where('user_id', $user->id);
                  })
                  ->orWhereHas('toAccount', function($query) use ($user) {
                      $query->where('user_id', $user->id);
                  });
            })
            ->active()
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // پیدا کردن حساب اصلی شرکت
        $companyAccount = Account::where('account_type', 'company')
            ->where('is_active', true)
            ->first();

        // تبدیل تاریخ‌های میلادی به شمسی و اضافه کردن type
        $transactions->transform(function ($transaction) use ($companyAccount) {
            // تبدیل payment_date به string شمسی (فقط تاریخ بدون timestamp)
            $paymentDate = $transaction->payment_date;
            if ($paymentDate instanceof \Carbon\Carbon) {
                $paymentDate = $paymentDate->format('Y-m-d');
            }
            $transaction->payment_date = DateHelper::miladiToShamsi($paymentDate, 'Y/m/d');
            
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

        return response()->json($transactions);
    }

    public function archive(Request $request)
    {
        $user = Auth::user();
        $record = Transaction::where('id', $request-> id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $record->archived = true;
        $record->save();
        $record->load(['fromAccount', 'toAccount', 'user']);

        // پیدا کردن حساب اصلی شرکت برای محاسبه type
        $companyAccount = Account::where('account_type', 'company')
            ->where('is_active', true)
            ->first();

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت (فقط تاریخ بدون timestamp)
        $paymentDate = $record->payment_date;
        if ($paymentDate instanceof \Carbon\Carbon) {
            $paymentDate = $paymentDate->format('Y-m-d');
        }
        $record->payment_date = DateHelper::miladiToShamsi($paymentDate, 'Y/m/d');

        // محاسبه type به صورت خودکار
        if ($companyAccount) {
            if ($record->from_account_id == $companyAccount->id) {
                $record->type = 'cost';
            } elseif ($record->to_account_id == $companyAccount->id) {
                $record->type = 'receive';
            } else {
                $fromAccount = $record->fromAccount;
                if ($fromAccount && $fromAccount->account_type === 'company') {
                    $record->type = 'cost';
                } else {
                    $record->type = 'receive';
                }
            }
        }

        return response()->json([
            'message' => 'تراکنش آرشیو شد.',
            'data' => $record,
        ]);
    }

    /**
     * بازیابی یک رکورد آرشیو شده
     */
    public function restore(Request $request)
    {
        $user = Auth::user();
        $record = Transaction::where('id', $request->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $record->archived = false;
        $record->save();
        $record->load(['fromAccount', 'toAccount', 'user']);

        // پیدا کردن حساب اصلی شرکت برای محاسبه type
        $companyAccount = Account::where('account_type', 'company')
            ->where('is_active', true)
            ->first();

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت (فقط تاریخ بدون timestamp)
        $paymentDate = $record->payment_date;
        if ($paymentDate instanceof \Carbon\Carbon) {
            $paymentDate = $paymentDate->format('Y-m-d');
        }
        $record->payment_date = DateHelper::miladiToShamsi($paymentDate, 'Y/m/d');

        // محاسبه type به صورت خودکار
        if ($companyAccount) {
            if ($record->from_account_id == $companyAccount->id) {
                $record->type = 'cost';
            } elseif ($record->to_account_id == $companyAccount->id) {
                $record->type = 'receive';
            } else {
                $fromAccount = $record->fromAccount;
                if ($fromAccount && $fromAccount->account_type === 'company') {
                    $record->type = 'cost';
                } else {
                    $record->type = 'receive';
                }
            }
        }

        return response()->json([
            'message' => 'تراکنش بازیابی شد.',
            'data' => $record,
        ]);
    }




}
