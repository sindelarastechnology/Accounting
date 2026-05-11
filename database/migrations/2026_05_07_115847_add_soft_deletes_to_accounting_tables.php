<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('wallets', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('purchases', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('contacts', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('products', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('wallets', fn (Blueprint $t) => $t->dropSoftDeletes());
    }
};
