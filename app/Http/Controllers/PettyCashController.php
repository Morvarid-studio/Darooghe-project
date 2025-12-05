<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PettyCashController extends Controller
{
    /**
     * دریافت حساب تنخواه کاربر
     */
    public function getMyPettyCashAccount(Request $request)
    {
        $user = Auth::user();

        $pettyCashAccount = Account::where('account_type', 'petty_cash')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['tags', 'user'])
            ->first();

        if (!$pettyCashAccount) {
            return response()->json([
                'message' => 'حساب تنخواهی برای شما یافت نشد.'
            ], 404);
        }

        return response()->json($pettyCashAccount);
    }

    /**
     * دریافت تراکنش‌های حساب تنخواه کاربر
     */
    public function getMyPettyCashTransactions(Request $request)
    {
        $user = Auth::user();

        // پیدا کردن حساب تنخواه کاربر
        $pettyCashAccount = Account::where('account_type', 'petty_cash')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$pettyCashAccount) {
            return response()->json([
                'message' => 'حساب تنخواهی برای شما یافت نشد.'
            ], 404);
        }

        // دریافت تراکنش‌هایی که از حساب تنخواه خرج شده یا به حساب تنخواه وارد شده
        $transactions = Transaction::with(['fromAccount', 'toAccount', 'user'])
            ->where(function($q) use ($pettyCashAccount) {
                $q->where('from_account_id', $pettyCashAccount->id)
                  ->orWhere('to_account_id', $pettyCashAccount->id);
            })
            ->active()
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // تبدیل تاریخ‌های میلادی به شمسی و اضافه کردن type
        $transactions->transform(function ($transaction) use ($pettyCashAccount) {
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
            // اگر از حساب تنخواه خرج شده → cost (خرج)
            // اگر به حساب تنخواه وارد شده → receive (دریافت)
            if ($transaction->from_account_id == $pettyCashAccount->id) {
                $transaction->type = 'cost';
            } else {
                $transaction->type = 'receive';
            }
            
            return $transaction;
        });

        return response()->json([
            'petty_cash_account' => $pettyCashAccount,
            'transactions' => $transactions
        ]);
    }

    /**
     * ثبت تراکنش برای حساب تنخواه کاربر
     */
    public function storeTransaction(Request $request)
    {
        $user = Auth::user();

        // پیدا کردن حساب تنخواه کاربر
        $pettyCashAccount = Account::where('account_type', 'petty_cash')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$pettyCashAccount) {
            return response()->json([
                'message' => 'حساب تنخواهی برای شما یافت نشد.'
            ], 404);
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

        // بررسی اینکه یکی از حساب‌ها باید حساب تنخواه کاربر باشد
        if ($validated['from_account_id'] != $pettyCashAccount->id && $validated['to_account_id'] != $pettyCashAccount->id) {
            return response()->json([
                'message' => 'یکی از حساب‌های مبدا یا مقصد باید حساب تنخواه شما باشد.'
            ], 422);
        }

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

        // تعیین type: اگر از حساب تنخواه خرج شده → cost، اگر به حساب تنخواه وارد شده → receive
        if ($validated['from_account_id'] == $pettyCashAccount->id) {
            $validated['type'] = 'cost';
        } else {
            $validated['type'] = 'receive';
        }

        $validated['invoice'] = null;
        if ($request->hasFile('invoice')) {
            $validated['invoice'] = $request->file('invoice')->store('invoices', 'public');
        }

        $transaction = Transaction::create($validated);

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

        return response()->json([
            'message' => 'تراکنش با موفقیت ثبت شد.',
            'data' => $transaction
        ], 201);
    }

    /**
     * دریافت موجودی حساب تنخواه کاربر
     */
    public function getMyPettyCashBalance(Request $request)
    {
        $user = Auth::user();

        // پیدا کردن حساب تنخواه کاربر
        $pettyCashAccount = Account::where('account_type', 'petty_cash')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$pettyCashAccount) {
            return response()->json([
                'message' => 'حساب تنخواهی برای شما یافت نشد.'
            ], 404);
        }

        // محاسبه موجودی از تراکنش‌ها
        $totalReceived = Transaction::where('to_account_id', $pettyCashAccount->id)
            ->where('archived', false)
            ->sum('amount_decimal');

        $totalCosts = Transaction::where('from_account_id', $pettyCashAccount->id)
            ->where('archived', false)
            ->sum('amount_decimal');

        $balance = $totalReceived - $totalCosts;

        return response()->json([
            'petty_cash_account' => $pettyCashAccount,
            'total_received' => $totalReceived,
            'total_costs' => $totalCosts,
            'balance' => $balance
        ]);
    }
}

