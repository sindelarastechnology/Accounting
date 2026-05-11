<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->foreignId('reversed_by_journal_id')->nullable()->after('reversed_journal_id')
                ->constrained('journals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropForeign(['reversed_by_journal_id']);
            $table->dropColumn('reversed_by_journal_id');
        });
    }
};
