1<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->string('phone', 11);
            $table->text('address');
            $table->date('birthday');
            $table->enum('gender', ['Male', 'Female']);
            $table->string('military');
            $table->enum('degree', [
                'دیپلم',
                'کاردانی',
                'لیسانس',
                'فوق لیسانس',
                'دکترا'
            ]);

            $table->text('emergency_contact_info')->nullable();
            $table->string('emergency_contact_number', 11)->nullable();

            $table->enum('education_status', [
                'در حال تحصیل',
                'فارغ‌التحصیل',
                'انصراف داده',
            ])->nullable();

            $table->enum('marital_status', [
                'مجرد',
                'متأهل'
            ])->nullable();

            $table->string('resume')->nullable(); // مسیر فایل
            $table->string('profile_photo')->nullable();
            $table->text('profession')->nullable();
            $table->text('languages')->nullable();
            $table->boolean('archive');
            $table->Decimal('salary', 12, 2)->default(0);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('information');
    }
};
