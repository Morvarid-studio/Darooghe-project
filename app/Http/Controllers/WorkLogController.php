<?php

namespace App\Http\Controllers;

use App\Models\Worklog;
use App\Helpers\DateHelper;
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
            'worklogs.*.work_date' => 'required|string', // دریافت به صورت شمسی
            'worklogs.*.work_hours' => 'required|numeric|min:0|max:24',
            'worklogs.*.description' => 'nullable|string|max:255',
        ]);

        $records = [];
        $threeDaysAgo = now()->subDays(3)->startOfDay();

        foreach ($validated['worklogs'] as $entry) {
            // اعتبارسنجی تاریخ شمسی
            if (!DateHelper::isValidShamsiDate($entry['work_date'])) {
                return response()->json([
                    'message' => 'تاریخ نامعتبر است. فرمت صحیح: Y/m/d (مثلاً 1403/07/15)',
                    'invalid_date' => $entry['work_date']
                ], 422);
            }

            // تبدیل تاریخ شمسی به میلادی
            $miladiDate = DateHelper::shamsiToMiladi($entry['work_date']);

            // جلوگیری از ثبت ساعات کاری قدیمی‌تر از سه روز قبل
            if (Carbon::parse($miladiDate) < $threeDaysAgo) {
                return response()->json([
                    'message' => 'ثبت ساعات کاری برای روزهای قدیمی‌تر از سه روز قبل مجاز نیست.',
                    'invalid_date' => $entry['work_date']
                ], 403);
            }

            $records[] = [
                'user_id' => $user->id,
                'work_date' => $miladiDate, // ذخیره به صورت میلادی
                'work_hours' => $entry['work_hours'],
                'description' => $entry['description'] ?? null,
                'archived' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Worklog::insert($records);

        // تبدیل تاریخ‌های میلادی به شمسی برای ارسال به کلاینت
        $responseData = [];
        foreach ($records as $record) {
            $responseData[] = [
                'work_date' => (string) DateHelper::miladiToShamsi($record['work_date']),
                'work_hours' => $record['work_hours'],
                'description' => $record['description'],
            ];
        }

        return response()->json([
            'message' => 'رکوردها با موفقیت ثبت شدند.',
            'data' => $responseData,
        ]);
    }


    /**
     * نمایش لیست رکوردهای فعال کاربر
     */
    public function show()
    {
        $user = Auth::user();

        $worklogs = Worklog::where('user_id', $user->id)
            ->active()
            ->orderBy('work_date', 'desc')
            ->get();

        // تبدیل تاریخ‌های میلادی به شمسی برای ارسال به کلاینت
        $worklogs->transform(function ($worklog) {
            // تبدیل به شمسی و اطمینان از اینکه string هست
            $worklog->work_date = (string) DateHelper::miladiToShamsi($worklog->work_date);
            return $worklog;
        });

        return response()->json($worklogs);
    }

    /**
     * آرشیو یک رکورد
     */
    public function archive(Request $request)
    {
        $user = Auth::user();

        $record = Worklog::where('id', $request->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // جلوگیری از آرشیو رکوردهای قدیمی‌تر از ۳ روز اخیر
        $threeDaysAgo = now()->subDays(3)->startOfDay();

        if ($record->work_date < $threeDaysAgo) {
            return response()->json([
                'message' => 'شما اجازه آرشیو کردن رکوردهای قدیمی‌تر از سه روز اخیر را ندارید.',
            ], 403);
        }

        $record->archived = true;
        $record->save();

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت
        $record->work_date = DateHelper::miladiToShamsi($record->work_date);

        return response()->json([
            'message' => 'رکورد با موفقیت آرشیو شد.',
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

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت
        $record->work_date = DateHelper::miladiToShamsi($record->work_date);

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
