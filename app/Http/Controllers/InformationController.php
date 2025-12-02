<?php

namespace App\Http\Controllers;

use App\Models\Information;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
class InformationController extends Controller
{

    /**
     * ذخیره اطلاعات جدید در جدول information
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'address'    => 'required|string',
            'birthday'   => 'required|string', // دریافت به صورت شمسی
            'gender'     => 'required|in:Male,Female',
            'military'   => 'required|string',
            'degree'     => 'required|in:Diploma,Associate,Bachelor,Master,PhD',
            'phone' => ['required','string','size:11','unique:information,phone','regex:/^09\d{9}$/'],
            'emergency_contact_info' => 'nullable|string',
            'emergency_contact_number' => 'nullable|string|size:11',
            'education_status' => 'nullable|in:Studying,Graduated,Dropped',
            'marital_status' => 'nullable|in:Single,Married',
            'profession' => 'nullable|string',
            'languages' => 'nullable|string',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'identity_document' => 'nullable|string|max:255',
        ]);

        // اعتبارسنجی و تبدیل تاریخ شمسی به میلادی
        if (!DateHelper::isValidShamsiDate($validated['birthday'])) {
            return response()->json([
                'message' => 'تاریخ تولد نامعتبر است. فرمت صحیح: Y/m/d (مثلاً 1403/07/15)'
            ], 422);
        }

        // تبدیل تاریخ شمسی به میلادی برای ذخیره در دیتابیس
        $validated['birthday'] = DateHelper::shamsiToMiladi($validated['birthday']);

        $validated['user_id'] = Auth::id();
        $validated['archive'] = false; // تنظیم archive به false
        $validated['profile_accepted'] = false; // اطلاعات جدید باید تایید شوند

        // آپلود فایل‌ها در صورت وجود
        if ($request->hasFile('resume')) {
            $validated['resume'] = $request->file('resume')->store('resumes', 'public');
        }

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        $information = Information::create($validated);

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت
        $information->birthday = DateHelper::miladiToShamsi($information->birthday);

        return response()->json([
            'message' => 'اطلاعات با موفقیت ثبت شد.',
            'data' => $information
        ], 201);
    }

    /**
     * نمایش اطلاعات کاربر لاگین شده (فقط اطلاعات فعال)
     */
    public function show()
    {
        $user = Auth::user();
        $information = Information::where('user_id', $user->id)
            ->where('archive', false)
            ->first();

        if (!$information) {
            return response()->json(['message' => 'اطلاعات یافت نشد.'], 404);
        }

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت
        $information->birthday = DateHelper::miladiToShamsi($information->birthday);

        return response()->json($information);
    }

    /**
     * دریافت آخرین اطلاعات کاربر (برای استفاده در فرم تکمیل اطلاعات)
     * این متد آخرین اطلاعات را برمی‌گرداند (چه تایید شده، چه رد شده، چه آرشیو شده)
     */
    public function getLatest()
    {
        $user = Auth::user();
        $information = Information::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$information) {
            return response()->json([
                'message' => 'اطلاعات یافت نشد.',
                'data' => null
            ]);
        }

        // تاریخ میلادی را به همان صورت برگردان (برای استفاده در date picker که میلادی می‌خواهد)
        // تاریخ در دیتابیس به صورت میلادی ذخیره شده است

        // اضافه کردن URL فایل‌ها
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
            'message' => 'آخرین اطلاعات کاربر',
            'data' => $information
        ]);
    }

    /**
     * بروزرسانی اطلاعات موجود
     */
    public function update(Request $request)
    {
        // 1) گرفتن آخرین رکورد (چه آرشیو شده، چه نشده) برای استفاده از فایل‌های قبلی
        $oldInfo = Information::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->first();

        // 2) اعتبارسنجی ورودی‌ها
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name'  => 'sometimes|required|string|max:255',
            'address'    => 'sometimes|required|string',
            'birthday'   => 'sometimes|required|string', // دریافت به صورت شمسی
            'gender'     => 'sometimes|required|in:Male,Female',
            'military'   => 'sometimes|required|string',
            'degree'     => 'sometimes|required|in:Diploma,Associate,Bachelor,Master,PhD',

            'phone' => [
                'nullable',
                'string',
                'size:11',
                'regex:/^09\d{9}$/',
                Rule::unique('information', 'phone')
                    ->ignore(optional($oldInfo)->id)
            ],

            'emergency_contact_info'   => 'nullable|string',
            'emergency_contact_number' => 'nullable|string|size:11',
            'education_status'         => 'nullable|in:Studying,Graduated,Dropped',
            'marital_status'           => 'nullable|in:Single,Married',
            'profession'               => 'nullable|string',
            'languages'                => 'nullable|string',

            'resume'        => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'identity_document' => 'nullable|string|max:255',
        ]);

        // اعتبارسنجی و تبدیل تاریخ شمسی به میلادی (اگر birthday ارسال شده باشد)
        if (isset($validated['birthday'])) {
            if (!DateHelper::isValidShamsiDate($validated['birthday'])) {
                return response()->json([
                    'message' => 'تاریخ تولد نامعتبر است. فرمت صحیح: Y/m/d (مثلاً 1403/07/15)'
                ], 422);
            }
            // تبدیل تاریخ شمسی به میلادی برای ذخیره در دیتابیس
            $validated['birthday'] = DateHelper::shamsiToMiladi($validated['birthday']);
        }



        if ($oldInfo) {
            $oldInfo->update(['archive' => true]);
        }


        $newData = $validated;
        $newData['user_id'] = auth()->id();
        $newData['archive'] = false;
        $newData['profile_accepted'] = false; // فعلاً false، بعداً accept می‌شه
        
        // اگر phone در validated نیست، از oldInfo بگیر
        if (!isset($newData['phone']) && $oldInfo) {
            $newData['phone'] = $oldInfo->phone;
        }
        
        // اگر فیلدهای required نیستند، از oldInfo بگیر
        $requiredFields = ['first_name', 'last_name', 'address', 'birthday', 'gender', 'military', 'degree'];
        foreach ($requiredFields as $field) {
            if (!isset($newData[$field]) && $oldInfo) {
                $newData[$field] = $oldInfo->$field;
            }
        }


        // آپلود فایل‌های جدید یا استفاده از فایل‌های قبلی
        if ($request->hasFile('resume')) {
            $newData['resume'] = $request->file('resume')->store('resumes', 'public');
        } elseif ($oldInfo && $oldInfo->resume) {
            // اگر فایل جدیدی آپلود نشده، از فایل قبلی استفاده کن
            $newData['resume'] = $oldInfo->resume;
        }

        if ($request->hasFile('profile_photo')) {
            $newData['profile_photo'] = $request->file('profile_photo')->store('profiles', 'public');
        } elseif ($oldInfo && $oldInfo->profile_photo) {
            // اگر فایل جدیدی آپلود نشده، از فایل قبلی استفاده کن
            $newData['profile_photo'] = $oldInfo->profile_photo;
        }


        $newInfo = Information::create($newData);

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت
        $newInfo->birthday = DateHelper::miladiToShamsi($newInfo->birthday);

        return response()->json([
            'message' => 'اطلاعات جدید ثبت و نسخه قبلی آرشیو شد.',
            'data' => $newInfo
        ]);
    }
}
