<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ابتدا role های موجود را در جدول roles ایجاد می‌کنیم
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'name' => 'admin', 'display_name' => 'مدیر سیستم', 'description' => 'دسترسی کامل به تمام بخش‌های سیستم', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'user', 'display_name' => 'کاربر عادی', 'description' => 'دسترسی محدود به بخش‌های عمومی', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // اضافه کردن فیلد جدید role_id (موقتاً nullable)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')
                ->constrained('roles')->onDelete('restrict');
        });

        // تبدیل role های موجود به role_id
        // ابتدا admin ها
        DB::table('users')
            ->where('role', 'admin')
            ->update(['role_id' => 1]);
        
        // سپس user ها
        DB::table('users')
            ->where('role', 'user')
            ->orWhereNull('role')
            ->update(['role_id' => 2]);

        // حذف فیلد enum قدیمی
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // حالا role_id را required می‌کنیم
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable(false)->default(2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin'])->default('user')->after('email');
        });
    }
};

