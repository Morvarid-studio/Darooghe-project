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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')
                ->onDelete('cascade');
            $table->decimal('hourly_wage', 15, 2)->default(0)->comment('حقوق ساعتی');
            $table->decimal('monthly_salary', 15, 2)->default(0)->comment('حقوق ثابت ماهانه');
            $table->boolean('is_active')->default(true)->comment('آیا این حقوق فعال است');
            $table->date('effective_from')->comment('تاریخ شروع اعمال');
            $table->date('effective_to')->nullable()->comment('تاریخ پایان اعمال');
            $table->text('notes')->nullable()->comment('یادداشت‌ها');
            $table->timestamps();

            // Index برای جستجوی سریع‌تر
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'effective_from', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};

