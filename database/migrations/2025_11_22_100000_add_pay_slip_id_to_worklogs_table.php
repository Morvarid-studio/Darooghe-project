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
        Schema::table('worklogs', function (Blueprint $table) {
            $table->foreignId('pay_slip_id')->nullable()->after('user_id')
                ->constrained('pay_slips')
                ->onDelete('set null')
                ->comment('شناسه فیش حقوقی که این ساعت کار در آن استفاده شده است');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worklogs', function (Blueprint $table) {
            $table->dropForeign(['pay_slip_id']);
            $table->dropColumn('pay_slip_id');
        });
    }
};

