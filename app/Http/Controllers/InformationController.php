<?php

namespace App\Http\Controllers;

use App\Models\Information;
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
            'birthday'   => 'required|date',
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

        $validated['user_id'] = Auth::id();
        $validated['archive'] = false; // تنظیم archive به false

        // آپلود فایل‌ها در صورت وجود
        if ($request->hasFile('resume')) {
            $validated['resume'] = $request->file('resume')->store('resumes', 'public');
        }

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        $information = Information::create($validated);

        return response()->json([
            'message' => 'اطلاعات با موفقیت ثبت شد.',
            'data' => $information
        ], 201);
    }

    /**
     * نمایش اطلاعات کاربر لاگین شده
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

        return response()->json($information);
    }

    /**
     * بروزرسانی اطلاعات موجود
     */
    public function update(Request $request)
    {
        // 1) گرفتن رکورد فعلی فعال
        $oldInfo = Information::where('user_id', auth()->id())
            ->where('archive', false)
            ->first();

        // 2) اعتبارسنجی ورودی‌ها
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name'  => 'sometimes|required|string|max:255',
            'address'    => 'sometimes|required|string',
            'birthday'   => 'sometimes|required|date',
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


        if ($request->hasFile('resume')) {
            $newData['resume'] = $request->file('resume')->store('resumes', 'public');
        }

        if ($request->hasFile('profile_photo')) {
            $newData['profile_photo'] = $request->file('profile_photo')->store('profiles', 'public');
        }


        $newInfo = Information::create($newData);

        return response()->json([
            'message' => 'اطلاعات جدید ثبت و نسخه قبلی آرشیو شد.',
            'data' => $newInfo
        ]);
    }
}
