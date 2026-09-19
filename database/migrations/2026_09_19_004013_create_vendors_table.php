<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code', 30)->unique();
            $table->string('name');
            $table->string('email', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website', 100)->nullable();
            $table->string('npwp', 20)->nullable();
            $table->boolean('is_pkp')->default(false)->index();
            $table->unsignedBigInteger('payment_term_id')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('payment_term_id')->references('id')->on('payment_terms')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
