<?php

namespace App\Http\Controllers;

use App\Models\Information;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            'phone'      => 'required|string|size:11|unique:information,phone|numeric|regex:/^(?:\+98|0)?9\d{9}$/',
            'emergency_contact_info' => 'nullable|string',
            'emergency_contact_number' => 'nullable|string|size:11',
            'education_status' => 'nullable|string',
            'marital_status' => 'nullable|string',
            'profession' => 'nullable|string',
            'languages' => 'nullable|string',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
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
    public function update(Request $request, Information $information)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name'  => 'sometimes|required|string|max:255',

            'address'    => 'sometimes|required|string',
            'birthday'   => 'sometimes|required|date',
            'gender'     => 'sometimes|required|in:Male,Female',
            'military'   => 'sometimes|required|string',
            'degree'     => 'sometimes|required|string',
            'phone'      => 'nullable|string|size:11|unique:information,phone,regex:/^(?:\+98|0)?9\d{9}$/' . $information->id,
            'emergency_contact_info' => 'nullable|string',
            'emergency_contact_number' => 'nullable|string|size:11',
            'education_status' => 'nullable|string',
            'marital_status' => 'nullable|string',
            'profession' => 'nullable|string',
            'languages' => 'nullable|string',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // آپلود جدید در صورت وجود
        if ($request->hasFile('resume')) {
            if ($information->resume) Storage::disk('public')->delete($information->resume);
            $validated['resume'] = $request->file('resume')->store('resumes', 'public');
        }

        if ($request->hasFile('profile_photo')) {
            if ($information->profile_photo) Storage::disk('public')->delete($information->profile_photo);
            $validated['profile_photo'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        $information->update($validated);

        return response()->json([
            'message' => 'اطلاعات با موفقیت بروزرسانی شد.',
            'data' => $information
        ]);
    }

    /**
     * حذف یک رکورد
     */
    public function destroy(Information $information)
    {
        if ($information->resume) Storage::disk('public')->delete($information->resume);
        if ($information->profile_photo) Storage::disk('public')->delete($information->profile_photo);

        $information->delete();

        return response()->json(['message' => 'اطلاعات با موفقیت حذف شد.']);
    }
}
