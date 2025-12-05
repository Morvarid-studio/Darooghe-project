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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // نام role مثل 'admin', 'user', 'finance', etc.
            $table->string('display_name'); // نام نمایشی مثل 'مدیر سیستم', 'کاربر عادی', etc.
            $table->text('description')->nullable(); // توضیحات
            $table->boolean('is_active')->default(true); // فعال/غیرفعال
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};


