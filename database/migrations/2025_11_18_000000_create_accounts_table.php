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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->enum('account_type', ['employee', 'company', 'external', 'petty_cash'])
                ->default('employee');
            $table->string('name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('branch_name', 255)->nullable();
            $table->string('branch_code', 255)->nullable();
            $table->string('account_number', 255)->nullable();
            $table->string('sheba', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('status', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints first if they exist
        if (Schema::hasTable('account_role')) {
            Schema::table('account_role', function (Blueprint $table) {
                $table->dropForeign(['account_id']);
            });
        }
        if (Schema::hasTable('account_tag')) {
            Schema::table('account_tag', function (Blueprint $table) {
                $table->dropForeign(['account_id']);
            });
        }
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (Schema::hasColumn('transactions', 'from_account_id')) {
                    $table->dropForeign(['from_account_id']);
                }
                if (Schema::hasColumn('transactions', 'to_account_id')) {
                    $table->dropForeign(['to_account_id']);
                }
            });
        }
        Schema::dropIfExists('accounts');
    }
};

