<?php

namespace App\Http\Controllers;

use App\Models\EmployeeBalance;
use App\Models\EmployeeBalanceHistory;
use Illuminate\Http\Request;

class EmployeeBalanceController extends Controller
{
    // گزارش وضعیت فعلی (همان VIEW)
    public function currentBalances()
    {
        $balances = EmployeeBalance::with('user')->paginate(20);
        return view('finance.balances.current', compact('balances'));
    }

    // گزارش ماهیانه از snapshot ها
    public function monthlyBalances(Request $request)
    {
        $month = $request->input('month');
        // فرمت پیشنهادی: 2025-03

        $history = EmployeeBalanceHistory::with('user')
            ->where('month', $month)
            ->paginate(20);

        return view('finance.balances.monthly', compact('history', 'month'));
    }

    // جزئیات یک کاربر در snapshot
    public function userMonthlyBalance($userId, Request $request)
    {
        $month = $request->input('month');

        $record = EmployeeBalanceHistory::with('user')
            ->where('user_id', $userId)
            ->where('month', $month)
            ->firstOrFail();

        return view('finance.balances.user-monthly', compact('record', 'month'));
    }
}
