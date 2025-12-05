<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تغییر نام جدول از bank_infos به accounts
        Schema::rename('bank_infos', 'accounts');
        
        Schema::table('accounts', function (Blueprint $table) {
            // user_id را nullable می‌کنیم (برای حساب‌های خارجی و شرکت)
            $table->foreignId('user_id')->nullable()->change();
            
            // اضافه کردن فیلدهای جدید
            $table->foreignId('account_category_id')->nullable()->after('user_id')
                ->constrained('account_categories')->onDelete('set null');
            
            $table->enum('account_type', ['employee', 'company', 'external'])
                ->default('employee')->after('account_category_id');
            
            $table->string('name')->nullable()->after('account_type'); // برای حساب‌های خارجی مثل "سوپرمارکت علی"
            
            // اضافه کردن فیلد description
            $table->text('description')->nullable()->after('sheba');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['account_category_id']);
            $table->dropColumn(['account_category_id', 'account_type', 'name', 'description']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
        
        Schema::rename('accounts', 'bank_infos');
    }
};

