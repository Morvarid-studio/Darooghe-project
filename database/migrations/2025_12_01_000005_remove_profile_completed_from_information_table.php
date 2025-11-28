<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * حذف profile_completed از جدول information
     * چون profile_completed از View محاسبه می‌شه (بر اساس وجود رکورد فعال)
     */
    public function up(): void
    {
        Schema::table('information', function (Blueprint $table) {
            $table->dropColumn('profile_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('information', function (Blueprint $table) {
            $table->boolean('profile_completed')->default(false)->after('archive');
        });
    }
};

