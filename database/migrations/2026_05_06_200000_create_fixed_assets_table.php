<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 16, 2);
            $table->decimal('salvage_value', 16, 2)->default(0);
            $table->integer('useful_life_years');
            $table->string('depreciation_method')->default('straight_line'); // straight_line, double_declining, sum_of_years
            $table->decimal('accumulated_depreciation', 16, 2)->default(0);
            $table->decimal('monthly_depreciation', 16, 2)->default(0);
            $table->boolean('is_fully_depreciated')->default(false);
            $table->foreignId('asset_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('depreciation_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status')->default('active'); // active, depreciated, disposed
            $table->date('disposed_date')->nullable();
            $table->decimal('disposal_amount', 16, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
