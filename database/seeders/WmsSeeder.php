<?php

namespace Database\Seeders;

use App\Enums\WarehouseLocationType;
use App\Models\Company;
use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\PurchaseOrder;
use App\Models\SubDistrict;
use App\Models\Village;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\Wms\InboundService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Throwable;

class WmsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $country = Country::firstOrCreate(['code' => 'IDN'], ['name' => 'Indonesia', 'is_active' => true]);
        $province = Province::firstOrCreate(['country_id' => $country->id, 'code' => '31'], ['name' => 'DKI Jakarta', 'is_active' => true]);
        $district = District::firstOrCreate(['province_id' => $province->id, 'code' => '31.74'], ['name' => 'Kota Jakarta Selatan', 'is_active' => true]);
        $subDistrict = SubDistrict::firstOrCreate(['district_id' => $district->id, 'code' => '31.74.07'], ['name' => 'Kebayoran Baru', 'is_active' => true]);
        $village = Village::firstOrCreate(['sub_district_id' => $subDistrict->id, 'code' => '31.74.07.1001'], ['name' => 'Senayan', 'postal_code' => '10270', 'is_active' => true]);

        $company = Company::firstOrCreate(['code' => 'GRU'], ['name' => 'PT. Gudang Rusa', 'is_active' => true]);

        $warehouse = Warehouse::firstOrCreate(
            ['warehouse_code' => 'WH-0001'],
            [
                'name' => 'Gudang Utama',
                'company_id' => $company->id,
                'address' => 'Jl. Senayan Raya No. 1',
                'country_id' => $country->id,
                'province_id' => $province->id,
                'district_id' => $district->id,
                'sub_district_id' => $subDistrict->id,
                'village_id' => $village->id,
                'postal_code' => $village->postal_code,
                'phone' => '+62 21 1234 5678',
                'is_active' => true,
            ],
        );

        $receiving = WarehouseLocation::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'location_code' => 'RECV'],
            ['name' => 'Receiving Bay', 'type' => WarehouseLocationType::Receiving, 'weight_capacity' => 5000, 'volume_capacity' => 100, 'is_active' => true],
        );

        WarehouseLocation::firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'location_code' => 'STOR'],
            ['name' => 'Main Storage', 'type' => WarehouseLocationType::Storage, 'weight_capacity' => 20000, 'volume_capacity' => 500, 'is_active' => true],
        );

        // Optionally receive a PO with outstanding inventory lines.
        $order = PurchaseOrder::query()
            ->whereIn('status', ['open', 'partially_received'])
            ->whereHas('lines', fn ($q) => $q->where('purchase_type', 'inventory')->whereColumn('received_quantity', '<', 'quantity'))
            ->first();

        if (! $order) {
            return;
        }

        try {
            $lines = $order->lines
                ->where('purchase_type', 'inventory')
                ->filter(fn ($line): bool => $line->received_quantity < $line->quantity)
                ->map(fn ($line): array => [
                    'purchase_order_line_id' => $line->id,
                    'quantity_received' => $line->quantity - $line->received_quantity,
                ])
                ->values()
                ->all();

            app(InboundService::class)->receive($order, [
                'warehouse_id' => $warehouse->id,
                'location_id' => $receiving->id,
                'receipt_date' => now()->toDateString(),
                'lines' => $lines,
            ]);
        } catch (Throwable) {
            // Skip the sample inbound when prerequisites are unavailable.
        }
    }
}
