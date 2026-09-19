<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_code', 30)->unique();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('vendor_id')->index();
            $table->unsignedBigInteger('purchase_request_id')->nullable()->index();
            $table->date('po_date');
            $table->date('expected_date')->nullable();
            $table->unsignedBigInteger('payment_term_id')->nullable()->index();
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->nullOnDelete();
            $table->foreign('payment_term_id')->references('id')->on('payment_terms')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
