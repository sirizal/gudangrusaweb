<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbound_receipt_id')->index();
            $table->unsignedBigInteger('purchase_order_line_id')->index();
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_cost', 20, 2)->default(0);
            $table->decimal('line_cost', 20, 2)->default(0);
            $table->timestamps();

            $table->foreign('inbound_receipt_id')->references('id')->on('inbound_receipts')->cascadeOnDelete();
            $table->foreign('purchase_order_line_id')->references('id')->on('purchase_order_lines')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_receipt_lines');
    }
};
