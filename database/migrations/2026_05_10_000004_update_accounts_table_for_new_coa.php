<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE accounts MODIFY COLUMN code VARCHAR(30) NOT NULL");
            DB::statement("ALTER TABLE accounts MODIFY COLUMN category VARCHAR(30) NOT NULL DEFAULT 'asset'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE accounts MODIFY COLUMN code VARCHAR(20) NOT NULL");
            DB::statement("ALTER TABLE accounts MODIFY COLUMN category ENUM('asset','liability','equity','revenue','cogs','expense') NOT NULL DEFAULT 'asset'");
        }
    }
};
