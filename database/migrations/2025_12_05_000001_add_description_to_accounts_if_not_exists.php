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
        // بررسی وجود ستون description
        if (!Schema::hasColumn('accounts', 'description')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->text('description')->nullable()->after('sheba');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // بررسی وجود ستون description قبل از حذف
        if (Schema::hasColumn('accounts', 'description')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};

