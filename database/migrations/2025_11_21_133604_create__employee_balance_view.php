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
        DB::statement("
            CREATE OR REPLACE VIEW employee_balances AS
            SELECT
                t.user_id,
                COALESCE(SUM(CASE WHEN t.type = 'cost' THEN t.amount ELSE 0 END),0) AS total_costs,
                COALESCE(SUM(CASE WHEN t.type = 'receive' THEN t.amount ELSE 0 END),0) AS total_received,
                COALESCE(SUM(CASE WHEN t.type = 'receive' THEN t.amount ELSE 0 END),0)
                  - COALESCE(SUM(CASE WHEN t.type = 'cost' THEN t.amount ELSE 0 END),0) AS balance
            FROM transactions t
            GROUP BY t.user_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS employee_balances");
    }
};
