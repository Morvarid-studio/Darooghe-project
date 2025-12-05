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
        // ابتدا باید داده‌های موجود را تبدیل کنیم
        // از آنجایی که from_account و to_account قبلاً string بودند و احتمالاً ID حساب‌ها را به صورت string ذخیره می‌کردند
        // باید آنها را به integer تبدیل کنیم
        
        Schema::table('transactions', function (Blueprint $table) {
            // حذف فیلدهای قدیمی string
            $table->dropColumn(['from_account', 'to_account']);
        });
        
        Schema::table('transactions', function (Blueprint $table) {
            // اضافه کردن فیلدهای جدید foreign key
            $table->foreignId('from_account_id')->after('handled_by')
                ->constrained('accounts')->onDelete('restrict');
            $table->foreignId('to_account_id')->after('from_account_id')
                ->constrained('accounts')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['from_account_id']);
            $table->dropForeign(['to_account_id']);
            $table->dropColumn(['from_account_id', 'to_account_id']);
        });
        
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('from_account')->after('handled_by');
            $table->string('to_account')->after('from_account');
        });
    }
};


