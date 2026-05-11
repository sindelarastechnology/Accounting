<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->foreignId('accumulated_depreciation_account_id')
                ->nullable()
                ->after('depreciation_account_id')
                ->constrained('accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropForeign(['accumulated_depreciation_account_id']);
            $table->dropColumn('accumulated_depreciation_account_id');
        });
    }
};
