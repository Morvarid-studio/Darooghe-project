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
        Schema::create('account_role', function (Blueprint $table) {
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->string('role');
            $table->primary(['account_id', 'role']);
        });

        Schema::create('account_category_role', function (Blueprint $table) {
            $table->foreignId('account_category_id')->constrained('account_categories')->onDelete('cascade');
            $table->string('role');
            $table->primary(['account_category_id', 'role']);
        });

        Schema::create('account_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('account_tags')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['account_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_role');
        Schema::dropIfExists('account_category_role');
        Schema::dropIfExists('account_tag');
    }
};

