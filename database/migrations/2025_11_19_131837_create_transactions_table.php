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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->date('payment_date');
            $table->decimal('amount_decimal', 18, 2); //میزان پرداختی
            $table->string('amount_string');
            $table->string('category'); // ????
            $table->string('type', 255); //نوع پرداخت
            $table->string('handled_by'); //توسط چه کسی
            $table->string('from_account'); //از چه حسابی
            $table->string('to_account'); // برای چه حسابی
            $table->string('description');
            $table->string('invoice')->nullable();//فاکتور
            $table->boolean('archived')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
