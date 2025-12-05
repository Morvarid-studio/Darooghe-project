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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->date('payment_date');
            $table->decimal('amount_decimal', 18, 2);
            $table->string('category');
            $table->string('handled_by');
            $table->foreignId('from_account_id')
                ->constrained('accounts')->onDelete('restrict');
            $table->foreignId('to_account_id')
                ->constrained('accounts')->onDelete('restrict');
            $table->string('description')->nullable();
            $table->string('invoice')->nullable();
            $table->boolean('archived')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
