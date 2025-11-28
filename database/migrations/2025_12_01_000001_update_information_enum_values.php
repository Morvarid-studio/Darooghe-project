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
        // SQLite از ALTER TABLE MODIFY COLUMN پشتیبانی نمی‌کند
        // این migration فقط برای MySQL اجرا می‌شود
        $driver = DB::getDriverName();
        
        if ($driver !== 'sqlite') {
            // تغییر enum degree
            DB::statement("ALTER TABLE information MODIFY COLUMN degree ENUM('Diploma', 'Associate', 'Bachelor', 'Master', 'PhD') NOT NULL");
            
            // تغییر enum education_status
            DB::statement("ALTER TABLE information MODIFY COLUMN education_status ENUM('Studying', 'Graduated', 'Dropped') NULL");
            
            // تغییر enum marital_status
            DB::statement("ALTER TABLE information MODIFY COLUMN marital_status ENUM('Single', 'Married') NULL");
        }
        // برای SQLite (در تست‌ها) این migration را skip می‌کنیم
        // چون در migration اصلی create_information_table enum‌ها درست تعریف شده‌اند
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // برگشت به مقادیر قبلی (اگر لازم بود)
        // توجه: اگر مقادیر قبلی رو نمی‌دونید، این بخش رو خالی بذارید
    }
};

