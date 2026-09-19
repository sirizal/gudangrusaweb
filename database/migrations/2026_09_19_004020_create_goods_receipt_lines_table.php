<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receipt_id')->index();
            $table->unsignedBigInteger('purchase_order_line_id')->index();
            $table->integer('quantity_received')->default(0);
            $table->timestamps();

            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->cascadeOnDelete();
            $table->foreign('purchase_order_line_id')->references('id')->on('purchase_order_lines')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
    }
};
