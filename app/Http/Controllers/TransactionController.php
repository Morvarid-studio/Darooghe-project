<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
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
            'amount_decimal'=>'required|numeric',
            'amount_string'=>'required|string',
            'category'=>'required|string',
            'type'=>'required|string',
            'handled_by'=>'required|string',
            'from_account'=>'required|string',
            'to_account'=>'required|string',
            'description'=>'string|nullable',
            'invoice'=>'file|mimes:pdf,doc,docx|max:2048|nullable',
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
        $validated['user_id'] = auth()->id();

        $validated['invoice'] = null;
        if ($request->hasFile('invoice')) {
            $validated['invoice']= $request->file('invoice')->store('invoices', 'public');
        }

        $transaction = Transaction::create($validated);

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت
        $transaction->payment_date = DateHelper::miladiToShamsi($transaction->payment_date);

        return response()->json([
            'message' => 'اطلاعات با موفقیت ثبت شد.',
            'data' => $transaction
        ], 201);
    }

    public function show()
    {
        $user = Auth::user();

        $transactions = Transaction::where('user_id', $user->id)
            ->active()
            ->orderBy('payment_date', 'desc')
            ->get();

        // تبدیل تاریخ‌های میلادی به شمسی برای ارسال به کلاینت
        $transactions->transform(function ($transaction) {
            $transaction->payment_date = DateHelper::miladiToShamsi($transaction->payment_date);
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

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت
        $record->payment_date = DateHelper::miladiToShamsi($record->payment_date);

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

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت
        $record->payment_date = DateHelper::miladiToShamsi($record->payment_date);

        return response()->json([
            'message' => 'تراکنش بازیابی شد.',
            'data' => $record,
        ]);
    }




}
