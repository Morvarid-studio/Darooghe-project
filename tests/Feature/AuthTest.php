<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker; // RefreshDatabase: دیتابیس را پاک می‌کند | WithFaker: برای ساخت داده‌های تصادفی

    /**
     * تست ثبت‌نام کاربر
     * 
     * این تست چه کار می‌کند؟
     * 1. یک درخواست POST به /api/register می‌فرستد
     * 2. بررسی می‌کند که پاسخ 201 (Created) برگردد
     * 3. بررسی می‌کند که ساختار JSON درست باشد
     * 4. بررسی می‌کند که کاربر در دیتابیس ایجاد شده باشد
     */
    public function test_user_can_register()
    {
        // ========== بخش 1: آماده‌سازی داده‌ها با Faker ==========
        // استفاده از Faker برای ساخت داده‌های تصادفی (نه ثابت)
        $userName = $this->faker->unique()->userName(); // یک username تصادفی و یکتا
        $email = $this->faker->unique()->safeEmail();    // یک email تصادفی و یکتا
        $password = 'Password123'; // رمز عبور را ثابت می‌گذاریم چون باید فرمت خاصی داشته باشد
        
        $userData = [
            'user_name' => $userName,
            'email' => $email,
            'password' => $password, // باید حداقل 8 کاراکتر و شامل عدد و حرف باشد
        ];

        // ========== بخش 2: ارسال درخواست ==========
        // postJson: یک درخواست POST با Content-Type: application/json می‌فرستد
        $response = $this->postJson('/api/register', $userData);

        // ========== بخش 3: بررسی پاسخ ==========
        // assertStatus: بررسی می‌کند که status code برابر 201 باشد
        $response->assertStatus(201);

        // assertJsonStructure: بررسی می‌کند که ساختار JSON درست باشد
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'user_name', 'email'],
            'token'
        ]);

        // assertJson: بررسی می‌کند که مقادیر خاصی در JSON وجود داشته باشد
        // حالا از متغیرهای $userName و $email استفاده می‌کنیم (نه مقادیر ثابت)
        $response->assertJson([
            'message' => 'Registration successful',
            'user' => [
                'user_name' => $userName, // استفاده از متغیر تصادفی
                'email' => $email,        // استفاده از متغیر تصادفی
            ]
        ]);

        // ========== بخش 4: بررسی دیتابیس ==========
        // assertDatabaseHas: بررسی می‌کند که رکوردی در دیتابیس وجود داشته باشد
        // حالا از متغیرهای $userName و $email استفاده می‌کنیم
        $this->assertDatabaseHas('users', [
            'user_name' => $userName, // استفاده از متغیر تصادفی
            'email' => $email,         // استفاده از متغیر تصادفی
        ]);
    }

    /**
     * تست لاگین کاربر
     * 
     * این تست چه کار می‌کند؟
     * 1. ابتدا یک کاربر در دیتابیس ایجاد می‌کند
     * 2. یک درخواست POST به /api/login می‌فرستد
     * 3. بررسی می‌کند که لاگین موفق باشد و token برگردد
     */
    public function test_user_can_login()
    {
        // ========== بخش 1: آماده‌سازی داده‌ها با Faker ==========
        // استفاده از Faker برای ساخت داده‌های تصادفی
        $userName = $this->faker->unique()->userName();
        $email = $this->faker->unique()->safeEmail();
        $password = 'Password123'; // رمز عبور را ثابت می‌گذاریم
        
        // ========== بخش 2: ایجاد کاربر در دیتابیس ==========
        // User::factory()->create(): یک کاربر تستی در دیتابیس ایجاد می‌کند
        // حالا از متغیرهای تصادفی استفاده می‌کنیم
        $user = User::factory()->create([
            'user_name' => $userName,
            'email' => $email,
            'password' => Hash::make($password), // رمز عبور را hash می‌کنیم
        ]);

        // ========== بخش 3: ارسال درخواست لاگین ==========
        $response = $this->postJson('/api/login', [
            'user_name' => $userName, // استفاده از متغیر تصادفی
            'password' => $password,
        ]);

        // ========== بخش 4: بررسی پاسخ ==========
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'user_name', 'email'],
                'token'
            ])
            ->assertJson([
                'message' => 'Login successful',
                'user' => [
                    'user_name' => $userName, // استفاده از متغیر تصادفی
                    'email' => $email,       // استفاده از متغیر تصادفی
                ]
            ]);

        // بررسی اینکه token برگردانده شده (خالی نباشد)
        $this->assertNotEmpty($response->json('token'));
    }
}

