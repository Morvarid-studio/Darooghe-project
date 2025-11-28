<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        // ابتدا VIEW را drop می‌کنیم (اگر وجود دارد)
        try {
            DB::statement("DROP VIEW IF EXISTS user_profile_status");
        } catch (\Exception $e) {
            // اگر VIEW وجود نداشت، خطایی نمی‌دهیم
        }
        
        if ($driver === 'sqlite') {
            DB::statement("
                CREATE VIEW user_profile_status AS
                SELECT
                    u.id AS user_id,
                    CASE WHEN i.id IS NOT NULL THEN 1 ELSE 0 END AS profile_completed,
                    COALESCE(i.profile_accepted, 0) AS profile_accepted
                FROM users u
                LEFT JOIN information i ON i.user_id = u.id AND i.archive = 0
            ");
        } else {
            DB::statement("
                CREATE OR REPLACE VIEW user_profile_status AS
                SELECT
                    u.id AS user_id,
                    CASE WHEN i.id IS NOT NULL THEN 1 ELSE 0 END AS profile_completed,
                    COALESCE(i.profile_accepted, 0) AS profile_accepted
                FROM users u
                LEFT JOIN information i ON i.user_id = u.id AND i.archive = 0
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS user_profile_status");
    }
};

