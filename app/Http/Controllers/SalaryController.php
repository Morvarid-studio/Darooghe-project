<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\User;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryController extends Controller
{
    /**
     * دریافت حقوق‌های یک کاربر
     */
    public function getUserSalaries($userId)
    {
        try {
            $salaries = Salary::where('user_id', $userId)
                ->orderBy('effective_from', 'desc')
                ->get();

            return response()->json([
                'data' => $salaries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'خطا در دریافت حقوق‌ها: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ثبت حقوق جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'hourly_wage' => 'required|numeric|min:0',
            'monthly_salary' => 'required|numeric|min:0',
            'effective_from' => 'required|string',
            'effective_to' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $userId = $request->user_id;

            // غیرفعال کردن حقوق فعال قبلی
            $activeSalary = Salary::where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if ($activeSalary) {
                // اگر effective_to ارسال نشده، یک روز قبل از تاریخ شروع جدید تنظیم می‌شود
                $newStartDate = \Carbon\Carbon::parse(DateHelper::shamsiToMiladi($request->effective_from));
                $previousEndDate = $newStartDate->copy()->subDay();
                
                $activeSalary->update([
                    'is_active' => false,
                    'effective_to' => $previousEndDate->format('Y-m-d'),
                ]);
            }

            // تبدیل تاریخ شمسی به میلادی
            $effectiveFrom = DateHelper::shamsiToMiladi($request->effective_from);
            $effectiveTo = $request->effective_to 
                ? DateHelper::shamsiToMiladi($request->effective_to)
                : null;

            // ایجاد حقوق جدید
            $salary = Salary::create([
                'user_id' => $userId,
                'hourly_wage' => $request->hourly_wage,
                'monthly_salary' => $request->monthly_salary,
                'is_active' => true,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => 'حقوق با موفقیت ثبت شد',
                'data' => $salary
            ], 201);
        });
    }

    /**
     * به‌روزرسانی حقوق
     */
    public function update(Request $request, $id)
    {
        $salary = Salary::findOrFail($id);

        $request->validate([
            'hourly_wage' => 'sometimes|numeric|min:0',
            'monthly_salary' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'effective_from' => 'sometimes|string',
            'effective_to' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $updateData = [];

        if ($request->has('hourly_wage')) {
            $updateData['hourly_wage'] = $request->hourly_wage;
        }

        if ($request->has('monthly_salary')) {
            $updateData['monthly_salary'] = $request->monthly_salary;
        }

        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->is_active;
        }

        if ($request->has('effective_from')) {
            $updateData['effective_from'] = DateHelper::shamsiToMiladi($request->effective_from);
        }

        if ($request->has('effective_to')) {
            $updateData['effective_to'] = $request->effective_to 
                ? DateHelper::shamsiToMiladi($request->effective_to)
                : null;
        }

        if ($request->has('notes')) {
            $updateData['notes'] = $request->notes;
        }

        $salary->update($updateData);

        return response()->json([
            'message' => 'حقوق با موفقیت به‌روزرسانی شد',
            'data' => $salary
        ]);
    }

}

