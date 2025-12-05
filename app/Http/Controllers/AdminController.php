<?php

namespace App\Http\Controllers;

use App\Models\Information;
use App\Models\User;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * دریافت لیست اطلاعات تایید نشده
     */
    public function getPendingProfiles(Request $request)
    {
        // دریافت اطلاعات کاربرانی که profile_completed = true و profile_accepted = false
        $pendingProfiles = Information::where('archive', false)
            ->where('profile_accepted', false)
            ->with('user:id,user_name,email,created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // تبدیل تاریخ میلادی به شمسی و اضافه کردن URL فایل‌ها برای هر رکورد
        // ساخت base URL با پورت 8080
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();
        // اگر پورت وجود ندارد یا پورت استاندارد است، پورت 8080 را اضافه کن
        if (!$port || ($port == 80 && $scheme == 'http') || ($port == 443 && $scheme == 'https')) {
            $port = 8080;
        }
        $baseUrl = $scheme . '://' . $host . ':' . $port;
        
        $pendingProfiles->transform(function ($profile) use ($baseUrl) {
            $profile->birthday = DateHelper::miladiToShamsi($profile->birthday);
            
            // اضافه کردن URL کامل برای فایل‌ها
            if ($profile->profile_photo) {
                // ساخت مسیر storage به صورت مستقیم
                $storagePath = '/storage/' . $profile->profile_photo;
                $profile->profile_photo_url = $baseUrl . $storagePath;
            }
            if ($profile->resume) {
                $storagePath = '/storage/' . $profile->resume;
                $profile->resume_url = $baseUrl . $storagePath;
            }
            
            return $profile;
        });

        return response()->json([
            'message' => 'لیست اطلاعات تایید نشده',
            'data' => $pendingProfiles
        ]);
    }

    /**
     * تایید اطلاعات کاربر
     */
    public function approveProfile(Request $request, $userId)
    {
        $information = Information::where('user_id', $userId)
            ->where('archive', false)
            ->where('profile_accepted', false)
            ->first();

        if (!$information) {
            return response()->json([
                'message' => 'اطلاعات کاربر یافت نشد یا قبلاً تایید شده است.'
            ], 404);
        }

        $information->update([
            'profile_accepted' => true
        ]);

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت
        $information->birthday = DateHelper::miladiToShamsi($information->birthday);
        $information->load('user:id,user_name,email');

        return response()->json([
            'message' => 'اطلاعات کاربر با موفقیت تایید شد.',
            'data' => $information
        ]);
    }

    /**
     * رد اطلاعات کاربر
     */
    public function rejectProfile(Request $request, $userId)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        $information = Information::where('user_id', $userId)
            ->where('archive', false)
            ->where('profile_accepted', false)
            ->first();

        if (!$information) {
            return response()->json([
                'message' => 'اطلاعات کاربر یافت نشد یا قبلاً تایید شده است.'
            ], 404);
        }

        // آرشیو کردن اطلاعات و ذخیره دلیل رد
        $information->update([
            'archive' => true,
            'rejection_reason' => $request->rejection_reason ?? null
        ]);

        // به‌روزرسانی اطلاعات کاربر برای برگرداندن profile_completed = false
        $user = $information->user;
        $user->refresh(); // برای به‌روزرسانی accessors

        // حذف تمام توکن‌های کاربر برای مجبور کردن به لاگین مجدد
        $user->tokens()->delete();

        return response()->json([
            'message' => 'اطلاعات کاربر رد و آرشیو شد. کاربر باید دوباره لاگین کند.',
            'data' => [
                'user_id' => $userId,
                'rejection_reason' => $information->rejection_reason,
                'user' => $user // برگرداندن اطلاعات به‌روزرسانی شده کاربر
            ]
        ]);
    }

    /**
     * دریافت جزئیات اطلاعات یک کاربر خاص
     */
    public function getUserProfile($userId)
    {
        $information = Information::where('user_id', $userId)
            ->where('archive', false)
            ->with('user:id,user_name,email,created_at')
            ->first();

        if (!$information) {
            return response()->json([
                'message' => 'اطلاعات کاربر یافت نشد.'
            ], 404);
        }

        // تبدیل تاریخ میلادی به شمسی
        $information->birthday = DateHelper::miladiToShamsi($information->birthday);
        
        // اضافه کردن URL کامل برای فایل‌ها
        $request = request();
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();
        if (!$port || ($port == 80 && $scheme == 'http') || ($port == 443 && $scheme == 'https')) {
            $port = 8080;
        }
        $baseUrl = $scheme . '://' . $host . ':' . $port;
        
        if ($information->profile_photo) {
            $storagePath = '/storage/' . $information->profile_photo;
            $information->profile_photo_url = $baseUrl . $storagePath;
        }
        if ($information->resume) {
            $storagePath = '/storage/' . $information->resume;
            $information->resume_url = $baseUrl . $storagePath;
        }

        return response()->json([
            'message' => 'اطلاعات کاربر',
            'data' => $information
        ]);
    }

    /**
     * دریافت لیست تمام کاربران (برای dropdown ها)
     */
    public function getAllUsers(Request $request)
    {
        $users = User::select('id', 'user_name', 'email')
            ->orderBy('user_name')
            ->get();

        return response()->json($users);
    }
}

