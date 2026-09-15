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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('account_code', 20);
            $table->string('account_name');
            $table->string('account_name_en')->nullable();
            $table->string('account_type');
            $table->string('account_sub_type')->nullable();
            $table->string('normal_balance')->default('debit');
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_group')->default(false)->index();
            $table->boolean('is_postable')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('financial_statement')->nullable()->index();
            $table->string('financial_statement_line')->nullable();
            $table->string('cash_flow_category')->nullable();
            $table->string('cash_flow_activity')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['company_id', 'account_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
