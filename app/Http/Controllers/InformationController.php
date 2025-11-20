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
    public function informationPost(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'address'    => 'required|string',
            'birthday'   => 'required|date',
            'gender'     => 'required|in:Male,Female',
            'military'   => 'required|string',
            'degree'     => 'required|string',
            'phone' => ['required','string','unique:information,phone','regex:/^(?:\+98|0)9\d{9}$/',],
            'emergency_contact_info' => 'nullable|string',
            'emergency_contact_number' => 'nullable|string|size:11',
            'education_status' => 'nullable|string',
            'marital_status' => 'nullable|string',
            'profession' => 'nullable|string',
            'languages' => 'nullable|string',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'archive' => false,
        ]);

        $validated['user_id'] = Auth::id();

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
     * نمایش یک رکورد خاص
     */
    public function showInformation(Information $information)
    {
        return response()->json($information);
    }

    /**
     * بروزرسانی اطلاعات موجود
     */
    public function update(Request $request)
    {
        // 1) گرفتن رکورد فعلی فعال
        $oldInfo = Information::where('user_id', auth()->id())
            ->where('archived', false)
            ->first();

        // 2) اعتبارسنجی ورودی‌ها
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name'  => 'sometimes|required|string|max:255',
            'address'    => 'sometimes|required|string',
            'birthday'   => 'sometimes|required|date',
            'gender'     => 'sometimes|required|in:Male,Female',
            'military'   => 'sometimes|required|string',
            'degree'     => 'sometimes|required|string',

            'phone' => [
                'nullable',
                'string',
                'regex:/^(?:\+98|0)9\d{9}$/',
                Rule::unique('information', 'phone')
                    ->ignore(optional($oldInfo)->id)
            ],

            'emergency_contact_info'   => 'nullable|string',
            'emergency_contact_number' => 'nullable|string|size:11',
            'education_status'         => 'nullable|string',
            'marital_status'           => 'nullable|string',
            'profession'               => 'nullable|string',
            'languages'                => 'nullable|string',

            'resume'        => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);



        if ($oldInfo) {
            $oldInfo->update(['archived' => true]);
        }


        $newData = $validated;
        $newData['user_id'] = auth()->id();
        $newData['archived'] = false;


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
