<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
        CREATE EVENT IF NOT EXISTS monthly_balance_snapshot
        ON SCHEDULE EVERY 1 MONTH
        STARTS DATE_FORMAT(NOW(), '%Y-%m-01 00:05:00')
        DO
        BEGIN
            INSERT INTO employee_balances_history
                (user_id, total_costs, total_received, balance, month, created_at, updated_at)
            SELECT
                u.id AS user_id,

        -- هزینه‌هایی که از حساب کاربر کم شده
                COALESCE(SUM(CASE WHEN t.from_account = b.id THEN t.amount_decimal ELSE 0 END),0) AS total_costs,

                -- دریافتی‌هایی که به حساب کاربر واریز شده
                COALESCE(SUM(CASE WHEN t.to_account = b.id THEN t.amount_decimal ELSE 0 END),0) AS total_received,

                -- مانده حساب
                COALESCE(SUM(CASE WHEN t.to_account = b.id THEN t.amount_decimal ELSE 0 END),0)
                - COALESCE(SUM(CASE WHEN t.from_account = b.id THEN t.amount_decimal ELSE 0 END),0) AS balance,

                DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AS month,
                NOW(),
                NOW()
        FROM users u
        LEFT JOIN bank_infos b ON b.user_id = u.id AND b.is_active = true
        LEFT JOIN transactions t
            ON t.from_account = b.id OR t.to_account = b.id
        WHERE t.created_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01')
        AND t.created_at < DATE_FORMAT(NOW(), '%Y-%m-01')
        GROUP BY u.id;
        END;
            ");
    }

    public function down(): void
    {
        DB::statement("DROP EVENT IF EXISTS monthly_balance_snapshot;");
    }
};
