<?php

namespace App\Http\Controllers;

use App\Models\PaySlip;
use App\Models\Worklog;
use App\Models\Information;
use App\Models\Account;
use App\Models\EmployeeBalanceHistory;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;

class PaySlipController extends Controller
{
    public function store(Request $request)
    {
        // ولیدیشن اولیه
        $request->validate([
            'user_id'        => 'required|exists:users,id',
            'payment_amount' => 'required|numeric|min:0',
            'receipt'        => 'nullable|string',
            'description'    => 'nullable|string'
        ]);

        $user_id = $request->user_id;

        return DB::transaction(function () use ($request, $user_id) {

            $month = Jalalian::now()->subMonth()->format('Y-m');


            $worklogs = Worklog::where('user_id', $user_id)
                ->where('is_paid', false)
                ->where('archived', false)
                ->get();

            $total_work_hours = $worklogs->sum('work_hours');


            $info = Information::where('user_id', $user_id)->first();
            if (!$info) {
                return response()->json(['error' => 'اطلاعات حقوق پایه یافت نشد'], 422);
            }
            $base_salary = $info->base_salary;


            $salary = $total_work_hours * $base_salary;


            $previousSlip = PaySlip::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->first();

            $previous_remaining_total = $previousSlip->remaining_salary_total ?? 0;
            $previous_total_balance   = $previousSlip->total_balance ?? 0;


            $balance_record = EmployeeBalanceHistory::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->first();

            $current_balance = $balance_record ? $balance_record->balance : 0;


            $account = Account::where('user_id', $user_id)
                ->where('is_active', true)
                ->where('account_type', 'employee')
                ->orderBy('id', 'desc')
                ->first();

            if (!$account) {
                return response()->json(['error' => 'اطلاعات بانکی فعالی یافت نشد'], 422);
            }

            $bankinfo_string = $account->bank_name . ' - ' . ($account->account_number ?? '');

            $payment_amount = $request->payment_amount;

            $remaining_this_month = $salary - $payment_amount;


            $max_payable = $salary + $previous_remaining_total;

            if ($payment_amount > $max_payable) {
                return response()->json([
                    'error' => 'مبلغ پرداختی بیش از مجموع حقوق این ماه و بدهی گذشته است.'
                ], 422);
            }

            $remaining_total = $previous_remaining_total + $remaining_this_month;


            $total_balance = $previous_total_balance + $current_balance;


            $paySlip = PaySlip::create([
                'user_id'                         => $user_id,
                'bankinfo'                        => $bankinfo_string,
                'base_salary'                     => $base_salary,
                'month'                           => $month,
                'total_work_hours'                => $total_work_hours,
                'salary'                          => $salary,
                'payment_amount'                  => $payment_amount,
                'remaining_salary_of_this_month'  => $remaining_this_month,
                'remaining_salary_total'          => $remaining_total,
                'balance'                         => $current_balance,
                'total_balance'                   => $total_balance,
                'receipt'                         => $request->receipt,
                'description'                     => $request->description,
            ]);




            Worklog::where('user_id', $user_id)
                ->where('is_paid', false)
                ->where('archived', false)
                ->update([
                    'is_paid' => true,
                ]);


            return response()->json([
                'message' => 'فیش حقوقی با موفقیت ثبت شد',
                'data'    => $paySlip
            ], 201);
        });
    }

    public function preview(Request $request)
    {
        // ولیدیشن
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user_id = $request->user_id;

        // ماه شمسی قبل
        $month = Jalalian::now()->subMonth()->format('Y-m');

        $worklogs = Worklog::where('user_id', $user_id)
            ->where('is_paid', false)
            ->where('archived', false)
            ->get();

        $total_work_hours = $worklogs->sum('work_hours');

        $info = Information::where('user_id', $user_id)->first();
        if (!$info) {
            return response()->json(['error' => 'اطلاعات حقوق پایه یافت نشد'], 422);
        }
        $base_salary = $info->base_salary;

        $salary = $total_work_hours * $base_salary;

        $previousSlip = PaySlip::where('user_id', $user_id)
            ->orderBy('id', 'desc')
            ->first();

        $previous_remaining_total = $previousSlip->remaining_salary_total ?? 0;
        $previous_total_balance   = $previousSlip->total_balance ?? 0;


        $balance_record = EmployeeBalanceHistory::where('user_id', $user_id)
            ->orderBy('id', 'desc')
            ->first();

        $current_balance = $balance_record ? $balance_record->balance : 0;

        return response()->json([
            'user_id'                    => $user_id,
            'month'                      => $month,
            'total_work_hours'           => $total_work_hours,
            'base_salary'                => $base_salary,
            'salary'                     => $salary,
            'previous_remaining_total'   => $previous_remaining_total,
            'remaining_salary_of_this_month' => $salary, // هنوز پرداخت نشده
            'remaining_salary_total'     => $previous_remaining_total + $salary,
            'balance'                     => $current_balance,
            'total_balance'               => $previous_total_balance + $current_balance,
        ]);
    }

}

