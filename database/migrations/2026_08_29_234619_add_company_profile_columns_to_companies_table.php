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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('address')->nullable();
            $table->string('postal_code', 5)->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 100)->nullable();

            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->unsignedBigInteger('province_id')->nullable()->index();
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->unsignedBigInteger('sub_district_id')->nullable()->index();
            $table->unsignedBigInteger('village_id')->nullable()->index();

            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            $table->foreign('province_id')->references('id')->on('provinces')->nullOnDelete();
            $table->foreign('district_id')->references('id')->on('districts')->nullOnDelete();
            $table->foreign('sub_district_id')->references('id')->on('sub_districts')->nullOnDelete();
            $table->foreign('village_id')->references('id')->on('villages')->nullOnDelete();

            $table->string('npwp', 20)->nullable()->index();
            $table->string('nib', 50)->nullable();
            $table->boolean('is_pkp')->default(false)->index();
            $table->string('tax_office', 100)->nullable();
            $table->date('tax_registration_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign([
                'country_id', 'province_id', 'district_id', 'sub_district_id', 'village_id',
            ]);
            $table->dropColumn([
                'address',
                'postal_code',
                'phone',
                'email',
                'website',
                'country_id',
                'province_id',
                'district_id',
                'sub_district_id',
                'village_id',
                'npwp',
                'nib',
                'is_pkp',
                'tax_office',
                'tax_registration_date',
            ]);
        });
    }
};
