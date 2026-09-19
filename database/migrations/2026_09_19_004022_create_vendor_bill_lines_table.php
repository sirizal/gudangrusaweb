<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bill_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_bill_id')->index();
            $table->string('purchase_type', 20)->default('inventory');
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 20, 2)->default(0);
            $table->decimal('line_total', 20, 2)->default(0);
            $table->timestamps();

            $table->foreign('vendor_bill_id')->references('id')->on('vendor_bills')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_bill_lines');
    }
};
