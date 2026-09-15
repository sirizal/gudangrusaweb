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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('address_code', 30);
            $table->string('label', 100)->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->unsignedBigInteger('province_id')->nullable()->index();
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->unsignedBigInteger('sub_district_id')->nullable()->index();
            $table->unsignedBigInteger('village_id')->nullable()->index();
            $table->string('postal_code', 5)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_billing')->default(false);
            $table->boolean('is_shipping')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            $table->foreign('province_id')->references('id')->on('provinces')->nullOnDelete();
            $table->foreign('district_id')->references('id')->on('districts')->nullOnDelete();
            $table->foreign('sub_district_id')->references('id')->on('sub_districts')->nullOnDelete();
            $table->foreign('village_id')->references('id')->on('villages')->nullOnDelete();

            $table->unique(['customer_id', 'address_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
