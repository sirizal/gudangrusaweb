<?php

namespace Database\Factories;

use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockTransfer> */
class StockTransferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'transfer_code' => 'TRF-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'warehouse_id' => Warehouse::factory(),
            'from_location_id' => WarehouseLocation::factory(),
            'to_location_id' => WarehouseLocation::factory(),
            'transfer_date' => now()->toDateString(),
            'status' => 'posted',
        ];
    }
}
