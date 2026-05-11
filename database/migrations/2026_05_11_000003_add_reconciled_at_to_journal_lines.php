<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->timestamp('reconciled_at')->nullable()->after('wallet_id');
            $table->foreignId('reconciled_by')->nullable()->after('reconciled_at')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropForeign(['reconciled_by']);
            $table->dropColumn(['reconciled_at', 'reconciled_by']);
        });
    }
};
