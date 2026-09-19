<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_code', 30)->unique();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('vendor_id')->index();
            $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
            $table->date('bill_date');
            $table->date('due_date');
            $table->unsignedBigInteger('payment_term_id')->nullable()->index();
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->decimal('paid_amount', 20, 2)->default(0);
            $table->unsignedBigInteger('journal_entry_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
            $table->foreign('payment_term_id')->references('id')->on('payment_terms')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_bills');
    }
};
