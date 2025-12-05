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
        Schema::table('accounts', function (Blueprint $table) {
            // حذف account_category_id (اگر وجود دارد)
            if (Schema::hasColumn('accounts', 'account_category_id')) {
                // حذف foreign key (اگر وجود دارد)
                try {
                    $table->dropForeign(['account_category_id']);
                } catch (\Exception $e) {
                    // اگر foreign key وجود نداشت، ادامه می‌دهیم
                }
                $table->dropColumn('account_category_id');
            }
        });
        
        Schema::table('accounts', function (Blueprint $table) {
            // اضافه کردن فیلدهای جدید (اگر وجود ندارند)
            if (!Schema::hasColumn('accounts', 'display_name')) {
                $table->string('display_name')->nullable()->after('account_type'); // نام نمایشی
            }
            if (!Schema::hasColumn('accounts', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('display_name'); // نام صاحب حساب
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // برگرداندن account_category_id
            $table->foreignId('account_category_id')->nullable()->after('user_id')
                ->constrained('account_categories')->onDelete('set null');
            
            // حذف فیلدهای جدید
            $table->dropColumn(['display_name', 'owner_name']);
        });
    }
};

