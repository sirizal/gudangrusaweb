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
        Schema::create('financial_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->string('statement_type')->index();
            $table->string('code', 50);
            $table->string('name');
            $table->unsignedInteger('sequence')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['statement_type', 'code']);
        });

        Schema::create('financial_statement_line_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_statement_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->boolean('include_descendants')->default(true);

            $table->unique(['financial_statement_line_id', 'account_id'], 'fs_line_account_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_statement_line_accounts');
        Schema::dropIfExists('financial_statement_lines');
    }
};
