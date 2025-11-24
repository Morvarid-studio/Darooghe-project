<?php

namespace App\Http\Controllers;

use App\Models\Worklog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;
class WorkLogController extends Controller
{
    /**
     * ثبت لیست رکوردهای ساعت کاری برای کاربر احراز هویت‌شده
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'worklogs' => 'required|array|min:1',
            'worklogs.*.work_date' => 'required|date',
            'worklogs.*.work_hours' => 'required|numeric|min:0|max:24',
            'worklogs.*.description' => 'nullable|string|max:255',
        ]);

        $records = [];

        foreach ($validated['worklogs'] as $entry) {
            $records[] = [
                'user_id' => $user->id,
                'work_date' => $entry['work_date'],
                'work_hours' => $entry['work_hours'],
                'description' => $entry['description'] ?? null,
                'archived' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Worklog::insert($records);

        return response()->json([
            'message' => 'رکوردها با موفقیت ثبت شدند.',
            'data' => $records,
        ]);
    }

    /**
     * نمایش لیست رکوردهای فعال کاربر
     */
    public function index()
    {
        $user = Auth::user();

        $worklogs = Worklog::where('user_id', $user->id)
            ->active()
            ->orderBy('work_date', 'desc')
            ->get();

        return response()->json($worklogs);
    }

    /**
     * آرشیو یک رکورد
     */
    public function archive(Request $request)
    {
        $user = Auth::user();
        $record = Worklog::where('id', $request-> id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $record->archived = true;
        $record->save();

        return response()->json([
            'message' => 'رکورد آرشیو شد.',
            'data' => $record,
        ]);
    }

    /**
     * بازیابی یک رکورد آرشیو شده
     */
    public function restore(Request $request)
    {
        $user = Auth::user();
        $record = Worklog::where('id', $request->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $record->archived = false;
        $record->save();

        return response()->json([
            'message' => 'رکورد بازیابی شد.',
            'data' => $record,
        ]);
    }
    public function MonthlyWorkHours()
    {
        $userId = auth()->id(); // گرفتن آیدی کاربر لاگین شده

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // گرفتن لاگ‌های غیر آرشیوی
        $logs = Worklog::where('user_id', $userId)
            ->where('archived', false)
            ->orderBy('work_date')
            ->get();

        $result = [];

        foreach ($logs as $log) {

            // تبدیل میلادی به شمسی
            $jDate = Jalalian::fromDateTime($log->work_date);

            // استخراج سال و ماه به صورت 1403-05
            $shamsiMonth = $jDate->format('Y-m');

            // جمع زدن ساعت‌ها
            if (!isset($result[$shamsiMonth])) {
                $result[$shamsiMonth] = 0;
            }

            $result[$shamsiMonth] += (float) $log->work_hours;

        }
        // تبدیل به خروجی JSON لیستی
        $formatted = [];

        foreach ($result as $month => $hours) {
            $formatted[] = [
                'month' => $month,
                'total_hours' => $hours
            ];
        }
        return response()->json($formatted);
    }

    public function WeeklyWorkHours()
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $logs = Worklog::where('user_id', $userId)
            ->where('archived', false)
            ->orderBy('work_date')
            ->get();

        $result = [];

        foreach ($logs as $log) {

            // تبدیل تاریخ میلادی به شمسی
            $jDate = Jalalian::fromDateTime($log->work_date);

            // استخراج سال و هفته شمسی
            $year = $jDate->getYear();
            $week = $jDate->getWeekOfYear(); // شماره هفته از 1 تا 53

            $key = sprintf("%d-W%02d", $year, $week);

            if (!isset($result[$key])) {
                $result[$key] = 0;
            }

            $result[$key] += (float) $log->work_hours;
        }

        // تبدیل نتیجه به آرایه قابل ارسال به فرانت
        $formatted = [];
        foreach ($result as $week => $hours) {
            $formatted[] = [
                'week' => $week,
                'total_hours' => $hours
            ];
        }

        return response()->json($formatted);
    }

    public function LastSevenDaysWorkHours()
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $today = Carbon::today();
        $startDate = $today->copy()->subDays(6);


        $logs = Worklog::where('user_id', $userId)
            ->where('archived', false)
            ->whereBetween('work_date', [$startDate, $today])
            ->orderBy('work_date')
            ->get();


        $result = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            $jDate = Jalalian::fromDateTime($date);

            $result[$jDate->format('Y-m-d')] = [
                'shamsi_date' => $jDate->format('Y/m/d'),
                'day_name' => $jDate->getDayName(),
                'total_hours' => 0
            ];
        }

        foreach ($logs as $log) {
            $dateKey = Carbon::parse($log->work_date)->format('Y-m-d');
            if (isset($result[$dateKey])) {
                $result[$dateKey]['total_hours'] += (float) $log->work_hours;
            }
        }

        $formatted = array_values($result);

        return response()->json($formatted);
    }
}
