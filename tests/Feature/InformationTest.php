<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Information;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class InformationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * تست ثبت اطلاعات کاربر (Profile)
     * 
     * این تست چه کار می‌کند؟
     * 1. یک کاربر ایجاد می‌کند و لاگین می‌کند
     * 2. اطلاعات پروفایل را ارسال می‌کند
     * 3. بررسی می‌کند که اطلاعات با موفقیت ثبت شده باشد
     */
    public function test_user_can_create_profile()
    {
        // ========== بخش 1: ایجاد کاربر و دریافت token ==========
        $user = User::factory()->create();
        $token = $user->createToken('api_token')->plainTextToken;

        // ========== بخش 2: آماده‌سازی داده‌های پروفایل ==========
        $profileData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'address' => $this->faker->address(),
            'birthday' => $this->faker->date('Y-m-d', '2000-01-01'),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'military' => $this->faker->randomElement(['معاف', 'تمام شده', 'در حال خدمت']),
            'degree' => $this->faker->randomElement(['Diploma', 'Associate', 'Bachelor', 'Master', 'PhD']),
            'phone' => '09' . $this->faker->numerify('#########'), // فرمت: 09xxxxxxxxx (11 کاراکتر)
            'emergency_contact_info' => $this->faker->name(),
            'emergency_contact_number' => '09' . $this->faker->numerify('#########'),
            'education_status' => $this->faker->randomElement(['Studying', 'Graduated', 'Dropped']),
            'marital_status' => $this->faker->randomElement(['Single', 'Married']),
            'profession' => $this->faker->jobTitle(),
            'languages' => 'فارسی، انگلیسی',
            'identity_document' => $this->faker->numerify('#########'), // اختیاری
        ];

        // ========== بخش 3: ارسال درخواست ==========
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/dashboard/information', $profileData);

        // ========== بخش 4: بررسی پاسخ ==========
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'first_name',
                    'last_name',
                    'phone',
                    'address',
                    'birthday',
                    'gender',
                    'degree',
                ]
            ])
            ->assertJson([
                'message' => 'اطلاعات با موفقیت ثبت شد.',
            ]);

        // ========== بخش 5: بررسی دیتابیس ==========
        // در SQLite boolean به صورت 0/1 ذخیره می‌شود
        $this->assertDatabaseHas('information', [
            'user_id' => $user->id,
            'first_name' => $profileData['first_name'],
            'last_name' => $profileData['last_name'],
            'phone' => $profileData['phone'],
        ]);
        
        // بررسی جداگانه archive (در SQLite boolean به صورت 0/1 ذخیره می‌شود)
        $information = Information::where('user_id', $user->id)->first();
        $this->assertEquals(0, $information->archive); // 0 = false
    }

    /**
     * تست دریافت اطلاعات کاربر
     */
    public function test_user_can_get_profile()
    {
        // ========== بخش 1: ایجاد کاربر و پروفایل ==========
        $user = User::factory()->create();
        $token = $user->createToken('api_token')->plainTextToken;

        // ایجاد پروفایل
        $information = Information::create([
            'user_id' => $user->id,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'address' => $this->faker->address(),
            'birthday' => $this->faker->date('Y-m-d'),
            'gender' => 'Male',
            'military' => 'معاف',
            'degree' => 'Bachelor',
            'phone' => '09123456789',
            'archive' => false,
        ]);

        // ========== بخش 2: دریافت اطلاعات ==========
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/dashboard/information');

        // ========== بخش 3: بررسی پاسخ ==========
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'user_id',
                'first_name',
                'last_name',
                'phone',
            ])
            ->assertJson([
                'user_id' => $user->id,
                'first_name' => $information->first_name,
            ]);
    }

    /**
     * تست بروزرسانی اطلاعات کاربر
     */
    public function test_user_can_update_profile()
    {
        // ========== بخش 1: ایجاد کاربر و پروفایل قدیمی ==========
        $user = User::factory()->create();
        $token = $user->createToken('api_token')->plainTextToken;

        // ایجاد پروفایل قدیمی
        $oldInformation = Information::create([
            'user_id' => $user->id,
            'first_name' => 'قدیمی',
            'last_name' => 'نام خانوادگی قدیمی',
            'address' => 'آدرس قدیمی',
            'birthday' => '1990-01-01',
            'gender' => 'Male',
            'military' => 'معاف',
            'degree' => 'Diploma',
            'phone' => '09111111111',
            'archive' => false,
        ]);

        // ========== بخش 2: آماده‌سازی داده‌های جدید ==========
        $updateData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'address' => $this->faker->address(),
            'degree' => 'Master',
            // phone را نمی‌فرستیم، باید از oldInfo گرفته شود
        ];

        // ========== بخش 3: ارسال درخواست بروزرسانی ==========
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/updateinformation', $updateData);

        // ========== بخش 4: بررسی پاسخ ==========
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'first_name',
                    'last_name',
                ]
            ])
            ->assertJson([
                'message' => 'اطلاعات جدید ثبت و نسخه قبلی آرشیو شد.',
            ]);

        // ========== بخش 5: بررسی دیتابیس ==========
        // بررسی اینکه پروفایل قدیمی archive شده (در SQLite boolean به صورت 0/1 ذخیره می‌شود)
        $oldInfo = Information::find($oldInformation->id);
        $this->assertEquals(1, $oldInfo->archive); // 1 = true

        // بررسی اینکه پروفایل جدید ایجاد شده
        $newInfo = Information::where('user_id', $user->id)
            ->where('archive', false)
            ->where('first_name', $updateData['first_name'])
            ->first();
        $this->assertNotNull($newInfo);
        $this->assertEquals(0, $newInfo->archive); // 0 = false
        $this->assertEquals(0, $newInfo->profile_accepted); // 0 = false
    }

    /**
     * تست validation: ثبت پروفایل بدون فیلدهای required
     */
    public function test_create_profile_requires_required_fields()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api_token')->plainTextToken;

        // ارسال درخواست بدون فیلدهای required
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/dashboard/information', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'address',
                'birthday',
                'gender',
                'military',
                'degree',
                'phone',
            ]);
    }

    /**
     * تست validation: phone باید 11 کاراکتر و فرمت 09xxxxxxxxx باشد
     */
    public function test_create_profile_validates_phone_format()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api_token')->plainTextToken;

        $profileData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'address' => $this->faker->address(),
            'birthday' => $this->faker->date('Y-m-d'),
            'gender' => 'Male',
            'military' => 'معاف',
            'degree' => 'Bachelor',
            'phone' => '123456789', // فرمت نامعتبر (کمتر از 11 کاراکتر و شروع با 09 نیست)
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/dashboard/information', $profileData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /**
     * تست validation: phone باید unique باشد
     */
    public function test_create_profile_rejects_duplicate_phone()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $token1 = $user1->createToken('api_token')->plainTextToken;
        $token2 = $user2->createToken('api_token')->plainTextToken;

        $phone = '09123456789';

        // ایجاد پروفایل برای کاربر اول
        $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/dashboard/information', [
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'address' => $this->faker->address(),
                'birthday' => $this->faker->date('Y-m-d'),
                'gender' => 'Male',
                'military' => 'معاف',
                'degree' => 'Bachelor',
                'phone' => $phone,
            ]);

        // تلاش برای ایجاد پروفایل با همان phone برای کاربر دوم
        $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->postJson('/api/dashboard/information', [
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'address' => $this->faker->address(),
                'birthday' => $this->faker->date('Y-m-d'),
                'gender' => 'Male',
                'military' => 'معاف',
                'degree' => 'Bachelor',
                'phone' => $phone,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /**
     * تست validation: degree باید یکی از مقادیر enum باشد
     */
    public function test_create_profile_validates_degree_enum()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api_token')->plainTextToken;

        $profileData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'address' => $this->faker->address(),
            'birthday' => $this->faker->date('Y-m-d'),
            'gender' => 'Male',
            'military' => 'معاف',
            'degree' => 'InvalidDegree', // مقدار نامعتبر
            'phone' => '09123456789',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/dashboard/information', $profileData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['degree']);
    }

    /**
     * تست validation: education_status باید یکی از مقادیر enum باشد
     */
    public function test_create_profile_validates_education_status_enum()
    {
        $user = User::factory()->create();
        $token = $user->createToken('api_token')->plainTextToken;

        $profileData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'address' => $this->faker->address(),
            'birthday' => $this->faker->date('Y-m-d'),
            'gender' => 'Male',
            'military' => 'معاف',
            'degree' => 'Bachelor',
            'phone' => '09123456789',
            'education_status' => 'InvalidStatus', // مقدار نامعتبر
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/dashboard/information', $profileData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['education_status']);
    }

    /**
     * تست: کاربر نمی‌تواند بدون token پروفایل بسازد
     */
    public function test_create_profile_requires_authentication()
    {
        $profileData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'address' => $this->faker->address(),
            'birthday' => $this->faker->date('Y-m-d'),
            'gender' => 'Male',
            'military' => 'معاف',
            'degree' => 'Bachelor',
            'phone' => '09123456789',
        ];

        $response = $this->postJson('/api/dashboard/information', $profileData);

        $response->assertStatus(401); // Unauthorized
    }
}

