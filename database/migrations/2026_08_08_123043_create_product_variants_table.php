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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('model_number')->nullable();
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->string('unit')->nullable();
            $table->unsignedInteger('available_stock')->default(0);
            $table->unsignedInteger('leadtime')->nullable();
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->string('supply_city')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
