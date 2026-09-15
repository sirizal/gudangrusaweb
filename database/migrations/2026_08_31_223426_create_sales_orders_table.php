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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 30)->unique();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('billing_address_id')->nullable()->index();
            $table->unsignedBigInteger('shipping_address_id')->nullable()->index();
            $table->date('order_date');
            $table->unsignedBigInteger('payment_term_id')->nullable()->index();
            $table->string('status', 30)->default('raised')->index();
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('billing_address_id')->references('id')->on('customer_addresses')->nullOnDelete();
            $table->foreign('shipping_address_id')->references('id')->on('customer_addresses')->nullOnDelete();
            $table->foreign('payment_term_id')->references('id')->on('payment_terms')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
