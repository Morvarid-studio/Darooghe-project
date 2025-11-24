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
                ->onUpdate('Cascade')->onDelete('restrict');
            $table->foreignId('bankinfo_id')->constrained('bank_infos')
                ->onUpdate('Cascade')->onDelete('restrict');
            $table->foreignId('base_salary')->constrained('information')
                ->onUpdate('Cascade')->onDelete('restrict');
            $table->date('month');
            $table->decimal('total_work_hours', 6, 2);
            $table->decimal('salary', 15, 2);
            $table->decimal('payment_amount', 15, 2); // فقط این فیلد در دسترس کاربر مورد نظر خواهد بود برای پر کردن
            $table->decimal('remaining_salary', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->decimal('total_balance', 15, 2);
            $table->string('receipt');
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
