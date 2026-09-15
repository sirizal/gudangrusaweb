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
        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('tax_type')->index();
            $table->decimal('rate', 8, 4)->default(0);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('payable_account_id')->nullable();
            $table->unsignedBigInteger('receivable_account_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('payable_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('receivable_account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_codes');
    }
};
