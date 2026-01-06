<?php

namespace App\Http\Controllers;

use App\Models\PaySlip;
use App\Models\Worklog;
use App\Models\Information;
use App\Models\Account;
use App\Models\EmployeeBalanceHistory;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaySlipController extends Controller
{
    /**
     * تبدیل ماه شمسی (Y-m) به تاریخ شروع و پایان ماه (میلادی)
     */
    private function getMonthDateRange($month)
    {
        // تبدیل ماه شمسی به تاریخ
        $parts = explode('-', $month);
        $year = (int)$parts[0];
        $monthNum = (int)$parts[1];
        
        // ایجاد تاریخ شروع ماه شمسی (روز اول ماه)
        $startJalali = new Jalalian($year, $monthNum, 1);
        $startDate = $startJalali->toCarbon();
        
        // محاسبه تعداد روزهای ماه شمسی
        $daysInMonth = $startJalali->getMonthDays();
        
        // ایجاد تاریخ پایان ماه شمسی (آخرین روز ماه)
        $endJalali = new Jalalian($year, $monthNum, $daysInMonth);
        $endDate = $endJalali->toCarbon()->endOfDay();
        
        return [$startDate, $endDate];
    }

    public function store(Request $request)
    {
        // ولیدیشن اولیه
        $request->validate([
            'user_id'        => 'required|exists:users,id',
            'payment_amount' => 'required|numeric|min:0',
            'receipt'        => 'nullable|string',
            'description'    => 'nullable|string'
        ]);

        $user = Auth::user();
        $user_id = $request->user_id;

        // بررسی دسترسی: فقط Admin می‌تواند برای کاربران دیگر فیش ثبت کند
        if ($user_id != $user->id && !$user->isAdmin()) {
            return response()->json([
                'error' => 'شما اجازه ثبت فیش برای کاربران دیگر را ندارید'
            ], 403);
        }

        return DB::transaction(function () use ($request, $user_id) {

            // ماه شمسی قبل
            $previousMonthCarbon = Carbon::now()->subMonth();
            $previousMonthJalali = Jalalian::fromDateTime($previousMonthCarbon);
            $month = $previousMonthJalali->format('Y-m');

            // دریافت بازه تاریخ ماه شمسی
            [$startDate, $endDate] = $this->getMonthDateRange($month);

            // دریافت worklog های پرداخت نشده در این ماه
            $worklogs = Worklog::where('user_id', $user_id)
                ->where('archived', false)
                ->whereNull('pay_slip_id') // چک کردن که قبلاً در فیش دیگری استفاده نشده باشد
                ->whereBetween('work_date', [$startDate, $endDate])
                ->get();

            // چک کردن که worklog تکراری وجود نداشته باشد
            if ($worklogs->isEmpty()) {
                return response()->json([
                    'error' => 'هیچ ساعت کاری پرداخت نشده‌ای برای این ماه یافت نشد.'
                ], 422);
            }

            $total_work_hours = $worklogs->sum('work_hours');


            // دریافت حقوق فعال کاربر
            $user = User::findOrFail($user_id);
            $activeSalary = $user->getActiveSalary();
            
            if (!$activeSalary) {
                return response()->json(['error' => 'اطلاعات حقوق فعال یافت نشد. لطفاً ابتدا حقوق کاربر را تنظیم کنید.'], 422);
            }

            $hourly_wage = $activeSalary->hourly_wage;
            $monthly_salary = $activeSalary->monthly_salary;
            
            // محاسبه حقوق: (ساعات کار × حقوق ساعتی) + حقوق ثابت ماهانه
            $salary = ($total_work_hours * $hourly_wage) + $monthly_salary;


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
                'base_salary'                     => $hourly_wage, // برای ذخیره در دیتابیس (فیلد موجود)
                'month'                           => $month,
                'total_work_hours'                => $total_work_hours,
                'salary'                          => $salary,
                'payment_amount'                  => $payment_amount,
                'remaining_salary_of_this_month'  => $remaining_this_month,
                'remaining_salary_total'          => $remaining_total,
                'balance'                         => $current_balance,
                'total_balance'                   => $total_balance,
                'receipt'                         => $request->receipt ?? '',
                'description'                     => $request->description ?? '',
            ]);

            // به‌روزرسانی worklog ها: تنظیم pay_slip_id و is_paid
            Worklog::whereIn('id', $worklogs->pluck('id'))
                ->update([
                    'pay_slip_id' => $paySlip->id,
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
        try {
            // استفاده از کاربر جاری یا user_id ارسالی (برای Admin)
            $user = Auth::user();
            $user_id = $request->input('user_id', $user->id);
            
            // تبدیل به integer در صورت نیاز
            $user_id = (int) $user_id;
            
            // اگر user_id ارسال شده و کاربر Admin نیست، فقط می‌تواند فیش خودش را ببیند
            if ($user_id != $user->id && !$user->isAdmin()) {
                return response()->json([
                    'error' => 'شما اجازه دسترسی به این اطلاعات را ندارید'
                ], 403);
            }

            // ماه شمسی قبل
            $previousMonthCarbon = Carbon::now()->subMonth();
            $previousMonthJalali = Jalalian::fromDateTime($previousMonthCarbon);
            $month = $previousMonthJalali->format('Y-m');

            // دریافت بازه تاریخ ماه شمسی
            [$startDate, $endDate] = $this->getMonthDateRange($month);

            // دریافت worklog های پرداخت نشده در این ماه
            $worklogs = Worklog::where('user_id', $user_id)
                ->where('archived', false)
                ->whereNull('pay_slip_id') // چک کردن که قبلاً در فیش دیگری استفاده نشده باشد
                ->whereBetween('work_date', [$startDate, $endDate])
                ->get();

            $total_work_hours = $worklogs->sum('work_hours');

            // دریافت حقوق فعال کاربر
            $targetUser = User::findOrFail($user_id);
            $activeSalary = $targetUser->getActiveSalary();
            
            if (!$activeSalary) {
                return response()->json(['error' => 'اطلاعات حقوق فعال یافت نشد. لطفاً ابتدا حقوق کاربر را تنظیم کنید.'], 422);
            }

            $hourly_wage = $activeSalary->hourly_wage;
            $monthly_salary = $activeSalary->monthly_salary;
            
            // محاسبه حقوق: (ساعات کار × حقوق ساعتی) + حقوق ثابت ماهانه
            $salary = ($total_work_hours * $hourly_wage) + $monthly_salary;

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
                'hourly_wage'                => $hourly_wage,
                'monthly_salary'             => $monthly_salary,
                'salary'                     => $salary,
                'previous_remaining_total'   => $previous_remaining_total,
                'remaining_salary_of_this_month' => $salary, // هنوز پرداخت نشده
                'remaining_salary_total'     => $previous_remaining_total + $salary,
                'balance'                     => $current_balance,
                'total_balance'               => $previous_total_balance + $current_balance,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PaySlipController@preview: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->input('user_id'),
            ]);
            
            return response()->json([
                'error' => 'خطا در پیش‌نمایش فیش: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * لیست فیش‌های کاربر جاری
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = PaySlip::where('user_id', $user->id)
            ->with(['workLogs' => function($q) {
                $q->select('id', 'work_date', 'work_hours', 'description', 'pay_slip_id');
            }])
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس ماه
        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        // فیلتر بر اساس سال
        if ($request->has('year')) {
            $query->where('month', 'like', $request->year . '-%');
        }

        // جستجو بر اساس شماره فیش
        if ($request->has('receipt')) {
            $query->where('receipt', 'like', '%' . $request->receipt . '%');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $paySlips = $query->paginate($perPage);

        return response()->json([
            'data' => $paySlips->items(),
            'pagination' => [
                'current_page' => $paySlips->currentPage(),
                'last_page' => $paySlips->lastPage(),
                'per_page' => $paySlips->perPage(),
                'total' => $paySlips->total(),
            ]
        ]);
    }

    /**
     * جزئیات یک فیش حقوقی
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $paySlip = PaySlip::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['workLogs' => function($q) {
                $q->select('id', 'work_date', 'work_hours', 'description', 'pay_slip_id')
                  ->orderBy('work_date', 'asc');
            }])
            ->first();

        if (!$paySlip) {
            return response()->json([
                'error' => 'فیش حقوقی یافت نشد'
            ], 404);
        }

        return response()->json([
            'data' => $paySlip
        ]);
    }

    /**
     * لیست همه فیش‌ها (برای Admin)
     */
    public function indexAdmin(Request $request)
    {
        $query = PaySlip::with(['user:id,user_name,email', 'workLogs'])
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس کاربر
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // فیلتر بر اساس ماه
        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        // فیلتر بر اساس سال
        if ($request->has('year')) {
            $query->where('month', 'like', $request->year . '-%');
        }

        // جستجو بر اساس شماره فیش
        if ($request->has('receipt')) {
            $query->where('receipt', 'like', '%' . $request->receipt . '%');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $paySlips = $query->paginate($perPage);

        return response()->json([
            'data' => $paySlips->items(),
            'pagination' => [
                'current_page' => $paySlips->currentPage(),
                'last_page' => $paySlips->lastPage(),
                'per_page' => $paySlips->perPage(),
                'total' => $paySlips->total(),
            ]
        ]);
    }

    /**
     * فیش‌های یک کاربر خاص (برای Admin)
     */
    public function indexByUser($user_id, Request $request)
    {
        $query = PaySlip::where('user_id', $user_id)
            ->with(['workLogs' => function($q) {
                $q->select('id', 'work_date', 'work_hours', 'description', 'pay_slip_id')
                  ->orderBy('work_date', 'asc');
            }])
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس ماه
        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $paySlips = $query->paginate($perPage);

        return response()->json([
            'data' => $paySlips->items(),
            'pagination' => [
                'current_page' => $paySlips->currentPage(),
                'last_page' => $paySlips->lastPage(),
                'per_page' => $paySlips->perPage(),
                'total' => $paySlips->total(),
            ]
        ]);
    }

    /**
     * فیش‌های یک ماه خاص (برای Admin)
     */
    public function indexByMonth($month, Request $request)
    {
        $query = PaySlip::where('month', $month)
            ->with(['user:id,user_name,email', 'workLogs'])
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس کاربر
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $paySlips = $query->paginate($perPage);

        return response()->json([
            'data' => $paySlips->items(),
            'pagination' => [
                'current_page' => $paySlips->currentPage(),
                'last_page' => $paySlips->lastPage(),
                'per_page' => $paySlips->perPage(),
                'total' => $paySlips->total(),
            ]
        ]);
    }

}

