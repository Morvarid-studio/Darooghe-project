<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver !== 'sqlite') {
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
                    COALESCE(SUM(CASE WHEN t.from_account_id = a.id THEN t.amount_decimal ELSE 0 END),0) AS total_costs,
                    COALESCE(SUM(CASE WHEN t.to_account_id = a.id THEN t.amount_decimal ELSE 0 END),0) AS total_received,
                    COALESCE(SUM(CASE WHEN t.to_account_id = a.id THEN t.amount_decimal ELSE 0 END),0)
                    - COALESCE(SUM(CASE WHEN t.from_account_id = a.id THEN t.amount_decimal ELSE 0 END),0) AS balance,
                    DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AS month,
                    NOW(),
                    NOW()
            FROM users u
            LEFT JOIN accounts a ON a.user_id = u.id AND a.is_active = true
            LEFT JOIN transactions t
                ON t.from_account_id = a.id OR t.to_account_id = a.id
            WHERE t.created_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01')
            AND t.created_at < DATE_FORMAT(NOW(), '%Y-%m-01')
            GROUP BY u.id;
            END;
                ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver !== 'sqlite') {
            DB::statement("DROP EVENT IF EXISTS monthly_balance_snapshot;");
        }
    }
};
