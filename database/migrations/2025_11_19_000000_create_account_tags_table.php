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
        Schema::create('account_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints first if they exist
        if (Schema::hasTable('account_tag')) {
            Schema::table('account_tag', function (Blueprint $table) {
                $table->dropForeign(['tag_id']);
            });
        }
        Schema::dropIfExists('account_tags');
    }
};

