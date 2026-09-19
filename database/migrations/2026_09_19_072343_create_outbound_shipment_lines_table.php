<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outbound_shipment_id')->index();
            $table->unsignedBigInteger('sales_order_line_id')->index();
            $table->integer('quantity_shipped')->default(0);
            $table->decimal('cost', 20, 2)->default(0);
            $table->timestamps();

            $table->foreign('outbound_shipment_id')->references('id')->on('outbound_shipments')->cascadeOnDelete();
            $table->foreign('sales_order_line_id')->references('id')->on('sales_order_lines')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_shipment_lines');
    }
};
