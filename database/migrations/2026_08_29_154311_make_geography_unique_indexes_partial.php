<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert the geography unique constraints to partial unique indexes that
     * exclude soft-deleted rows. Laravel's `unique` validation rule already
     * ignores trashed records, so a full constraint would otherwise reject
     * re-creating a soft-deleted code.
     */
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropUnique(['country_id', 'code']);
        });
        Schema::table('districts', function (Blueprint $table) {
            $table->dropUnique(['province_id', 'code']);
        });
        Schema::table('sub_districts', function (Blueprint $table) {
            $table->dropUnique(['district_id', 'code']);
        });
        Schema::table('villages', function (Blueprint $table) {
            $table->dropUnique(['sub_district_id', 'code']);
        });

        DB::statement('CREATE UNIQUE INDEX countries_code_unique ON countries (code) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX provinces_country_id_code_unique ON provinces (country_id, code) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX districts_province_id_code_unique ON districts (province_id, code) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX sub_districts_district_id_code_unique ON sub_districts (district_id, code) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX villages_sub_district_id_code_unique ON villages (sub_district_id, code) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropUnique(['country_id', 'code']);
        });
        Schema::table('districts', function (Blueprint $table) {
            $table->dropUnique(['province_id', 'code']);
        });
        Schema::table('sub_districts', function (Blueprint $table) {
            $table->dropUnique(['district_id', 'code']);
        });
        Schema::table('villages', function (Blueprint $table) {
            $table->dropUnique(['sub_district_id', 'code']);
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->unique('code');
        });
        Schema::table('provinces', function (Blueprint $table) {
            $table->unique(['country_id', 'code']);
        });
        Schema::table('districts', function (Blueprint $table) {
            $table->unique(['province_id', 'code']);
        });
        Schema::table('sub_districts', function (Blueprint $table) {
            $table->unique(['district_id', 'code']);
        });
        Schema::table('villages', function (Blueprint $table) {
            $table->unique(['sub_district_id', 'code']);
        });
    }
};
