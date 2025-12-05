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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_name')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('role_id')->default(2)
                ->constrained('roles')->onDelete('restrict');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints first if they exist
        $tablesWithUserForeignKey = [
            'employee_balances_history',
            'transactions',
            'pay_slips',
            'information',
            'worklogs',
            'sessions'
        ];
        
        foreach ($tablesWithUserForeignKey as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'user_id')) {
                try {
                    // Try to get foreign key constraint name and drop it
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = ? 
                        AND COLUMN_NAME = 'user_id'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ", [$tableName]);
                    
                    foreach ($foreignKeys as $fk) {
                        DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    }
                } catch (\Exception $e) {
                    // Foreign key might not exist or already dropped, continue
                }
            }
        }
        
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
