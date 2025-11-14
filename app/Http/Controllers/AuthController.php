<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * ثبت‌نام کاربر جدید
     */
    public function registerPost(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|string|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/',

        ]);

        $user = User::create([
            'user_name' => $request->user_name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        // ایجاد توکن بلافاصله بعد از ثبت‌نام
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    /**
     * ورود کاربر و صدور توکن
     */
    public function loginPost(Request $request)
    {
        $credentials = $request->validate([
            'user_name' => 'required|exists:users,user_name',
            'password' => 'required|min:5'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * خروج کاربر (حذف توکن جاری)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * داشبورد یا پروفایل کاربر
     */
    public function dashboard(Request $request)
    {
        return response()->json([
            'message' => 'Welcome to your dashboard',
            'user' => $request->user()
        ]);
    }
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'current_password' => ['required', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'], // new_password_confirmation
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'رمز عبور فعلی اشتباه است.'
            ], 403);
        }

        $user->username = $validated['username'];

        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return response()->json([
            'message' => 'اطلاعات با موفقیت به‌روزرسانی شد.',
            'user' => $user
        ]);
    }
}
