<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_cash_sale')->default(false)->after('created_by');
            $table->foreignId('wallet_id')->nullable()->after('is_cash_sale')
                ->constrained('wallets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['wallet_id']);
            $table->dropColumn(['is_cash_sale', 'wallet_id']);
        });
    }
};
