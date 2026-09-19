<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_payment_id')->index();
            $table->unsignedBigInteger('vendor_bill_id')->index();
            $table->decimal('amount', 20, 2)->default(0);
            $table->timestamps();

            $table->foreign('vendor_payment_id')->references('id')->on('vendor_payments')->cascadeOnDelete();
            $table->foreign('vendor_bill_id')->references('id')->on('vendor_bills')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payment_lines');
    }
};
