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
        Schema::create('pay_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')
                ->onDelete('restrict');
            $table->string('bankinfo');
            $table->decimal('base_salary', 15, 2);
            $table->string('month');
            $table->decimal('total_work_hours', 5, 2); // تغییر از 3,2 به 5,2 برای پشتیبانی از ساعات کاری بیشتر (تا 999.99)
            $table->decimal('salary', 15, 2);
            $table->decimal('payment_amount', 15, 2);
            $table->decimal('remaining_salary_of_this_month', 15, 2);
            $table->decimal('remaining_salary_total', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->decimal('total_balance', 15, 2);
            $table->string('receipt');
            $table->decimal('insurance', 15, 2)->default(0);
            $table->decimal('financial_facilities', 15, 2)->default(0);
            $table->decimal('remaining_facilities', 15, 2)->default(0);
            $table->decimal('monthly_facility_payment', 15, 2)->default(0);
            $table->decimal('total_facility_payment', 15, 2)->default(0);
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_slips');
    }
};
