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
        Schema::table('pay_slips', function (Blueprint $table) {
            // تغییر از decimal(3, 2) به decimal(5, 2) برای پشتیبانی از ساعات کاری بیشتر
            // decimal(3, 2) فقط تا 9.99 را پشتیبانی می‌کند
            // decimal(5, 2) تا 999.99 را پشتیبانی می‌کند
            $table->decimal('total_work_hours', 5, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pay_slips', function (Blueprint $table) {
            $table->decimal('total_work_hours', 3, 2)->change();
        });
    }
};

