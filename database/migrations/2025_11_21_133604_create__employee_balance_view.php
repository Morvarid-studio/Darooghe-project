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
        $driver = DB::getDriverName();
        
        // SQLite از CREATE OR REPLACE VIEW پشتیبانی نمی‌کند
        // پس ابتدا VIEW را drop می‌کنیم (اگر وجود دارد) و سپس ایجاد می‌کنیم
        if ($driver === 'sqlite') {
            // در SQLite ابتدا VIEW را drop می‌کنیم (اگر وجود دارد)
            try {
                DB::statement("DROP VIEW IF EXISTS employee_balances");
            } catch (\Exception $e) {
                // اگر VIEW وجود نداشت، خطایی نمی‌دهیم
            }
            
            // سپس VIEW را ایجاد می‌کنیم
            DB::statement("
                CREATE VIEW employee_balances AS
                SELECT
                    t.user_id,
                    COALESCE(SUM(CASE WHEN t.type = 'cost' THEN t.amount_decimal ELSE 0 END),0) AS total_costs,
                    COALESCE(SUM(CASE WHEN t.type = 'receive' THEN t.amount_decimal ELSE 0 END),0) AS total_received,
                    COALESCE(SUM(CASE WHEN t.type = 'receive' THEN t.amount_decimal ELSE 0 END),0)
                      - COALESCE(SUM(CASE WHEN t.type = 'cost' THEN t.amount_decimal ELSE 0 END),0) AS balance
                FROM transactions t
                GROUP BY t.user_id
            ");
        } else {
            // برای MySQL و سایر دیتابیس‌ها از CREATE OR REPLACE استفاده می‌کنیم
            DB::statement("
                CREATE OR REPLACE VIEW employee_balances AS
                SELECT
                    t.user_id,
                    COALESCE(SUM(CASE WHEN t.type = 'cost' THEN t.amount_decimal ELSE 0 END),0) AS total_costs,
                    COALESCE(SUM(CASE WHEN t.type = 'receive' THEN t.amount_decimal ELSE 0 END),0) AS total_received,
                    COALESCE(SUM(CASE WHEN t.type = 'receive' THEN t.amount_decimal ELSE 0 END),0)
                      - COALESCE(SUM(CASE WHEN t.type = 'cost' THEN t.amount_decimal ELSE 0 END),0) AS balance
                FROM transactions t
                GROUP BY t.user_id
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS employee_balances");
    }
};
