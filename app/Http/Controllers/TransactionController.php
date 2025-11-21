<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function store(Request $request)
    {

        $validated = $request->validate([
            'payment_date' => 'required|date',
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
        $validated['archived'] = $request->input('archived', false);
        $validated['user_id'] = auth()->id();

        $validated['invoice'] = null;
        if ($request->hasFile('invoice')) {
            $validated['invoice']= $request->file('invoice')->store('invoices', 'public');
        }

        $transaction = Transaction::create($validated);

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
            ->orderBy('work_date', 'desc')
            ->get();

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

        return response()->json([
            'message' => 'تراکنش بازیابی شد.',
            'data' => $record,
        ]);
    }




}
