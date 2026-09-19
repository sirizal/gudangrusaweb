<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id')->index();
            $table->string('purchase_type', 20)->default('inventory');
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 20, 2)->default(0);
            $table->decimal('line_total', 20, 2)->default(0);
            $table->timestamps();

            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_lines');
    }
};
