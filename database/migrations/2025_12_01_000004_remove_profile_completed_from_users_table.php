<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * توجه: این migration اختیاری است. اگر می‌خواهید profile_completed را از جدول users حذف کنید،
     * این migration را اجرا کنید. در غیر این صورت، می‌توانید آن را نادیده بگیرید.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('profile_completed')->default(false)->after('email');
        });
    }
};

