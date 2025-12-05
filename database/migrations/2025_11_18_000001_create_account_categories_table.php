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
        Schema::create('account_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints first if they exist
        if (Schema::hasTable('account_category_role')) {
            Schema::table('account_category_role', function (Blueprint $table) {
                $table->dropForeign(['account_category_id']);
            });
        }
        Schema::dropIfExists('account_categories');
    }
};
