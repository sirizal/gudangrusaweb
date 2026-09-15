<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeographySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $indonesia = Country::firstOrCreate(
            ['code' => 'IDN'],
            ['name' => 'Indonesia', 'is_active' => true],
        );

        $jakarta = Province::firstOrCreate(
            ['country_id' => $indonesia->id, 'code' => '31'],
            ['name' => 'DKI Jakarta', 'is_active' => true],
        );

        $jakartaSelatan = District::firstOrCreate(
            ['province_id' => $jakarta->id, 'code' => '31.74'],
            ['name' => 'Kota Jakarta Selatan', 'is_active' => true],
        );

        $kebayoranBaru = SubDistrict::firstOrCreate(
            ['district_id' => $jakartaSelatan->id, 'code' => '31.74.07'],
            ['name' => 'Kebayoran Baru', 'is_active' => true],
        );

        foreach ([
            ['code' => '31.74.07.1001', 'name' => 'Senayan', 'postal_code' => '10270'],
            ['code' => '31.74.07.1002', 'name' => 'Gunung', 'postal_code' => '12120'],
            ['code' => '31.74.07.1003', 'name' => 'Kramat Pela', 'postal_code' => '12130'],
        ] as $village) {
            Village::firstOrCreate(
                ['sub_district_id' => $kebayoranBaru->id, 'code' => $village['code']],
                ['name' => $village['name'], 'postal_code' => $village['postal_code'], 'is_active' => true],
            );
        }
    }
}
