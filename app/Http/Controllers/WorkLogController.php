<?php

namespace App\Http\Controllers;

use App\Models\Worklog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
