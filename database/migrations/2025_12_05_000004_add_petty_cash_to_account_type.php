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
        // تغییر enum account_type برای اضافه کردن 'petty_cash'
        DB::statement("ALTER TABLE accounts MODIFY COLUMN account_type ENUM('employee', 'company', 'external', 'petty_cash') DEFAULT 'employee'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف 'petty_cash' از enum (فقط اگر هیچ رکوردی با این نوع وجود نداشته باشد)
        DB::statement("ALTER TABLE accounts MODIFY COLUMN account_type ENUM('employee', 'company', 'external') DEFAULT 'employee'");
    }
};

