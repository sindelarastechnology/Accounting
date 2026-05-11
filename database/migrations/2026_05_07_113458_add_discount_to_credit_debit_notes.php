<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->decimal('discount_percent', 8, 4)->default(0)->after('subtotal');
            $table->decimal('discount_amount', 18, 4)->default(0)->after('discount_percent');
        });

        Schema::table('debit_notes', function (Blueprint $table) {
            $table->decimal('discount_percent', 8, 4)->default(0)->after('subtotal');
            $table->decimal('discount_amount', 18, 4)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discount_amount']);
        });

        Schema::table('debit_notes', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discount_amount']);
        });
    }
};
