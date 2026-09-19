<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_code', 30)->unique();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('from_location_id')->index();
            $table->unsignedBigInteger('to_location_id')->index();
            $table->date('transfer_date');
            $table->string('status', 30)->default('posted')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('from_location_id')->references('id')->on('warehouse_locations')->cascadeOnDelete();
            $table->foreign('to_location_id')->references('id')->on('warehouse_locations')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
