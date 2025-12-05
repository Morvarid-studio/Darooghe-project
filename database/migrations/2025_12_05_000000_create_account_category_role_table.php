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
        Schema::create('account_category_role', function (Blueprint $table) {
            $table->foreignId('account_category_id')->constrained('account_categories')->onDelete('cascade');
            $table->string('role'); // 'user', 'admin', ...
            $table->primary(['account_category_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_category_role');
    }
};

