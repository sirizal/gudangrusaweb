<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_code', 30)->unique();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->date('adjustment_date');
            $table->string('status', 30)->default('posted')->index();
            $table->decimal('total_cost', 20, 2)->default(0);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('location_id')->references('id')->on('warehouse_locations')->cascadeOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
