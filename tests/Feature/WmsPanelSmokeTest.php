<?php

use App\Enums\Role;
use App\Models\Role as RoleModel;
use App\Models\User;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\WmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('allows warehouse and accounting roles and denies others', function () {
    $warehouse = User::factory()->create();
    $warehouse->roles()->attach(RoleModel::where('code', Role::Warehouse->value)->firstOrCreate(['code' => Role::Warehouse->value], ['name' => Role::Warehouse->getLabel()]));
    expect($warehouse->canAccessPanel(filament()->getPanel('wms')))->toBeTrue();

    $viewer = User::factory()->create();
    $viewer->roles()->attach(RoleModel::where('code', Role::Viewer->value)->firstOrCreate(['code' => Role::Viewer->value], ['name' => Role::Viewer->getLabel()]));
    expect($viewer->canAccessPanel(filament()->getPanel('wms')))->toBeFalse();
});

it('renders the wms dashboard and resource pages', function () {
    $this->seed(AccountingSeeder::class);
    $this->seed(WmsSeeder::class);

    $admin = User::factory()->create();
    $admin->roles()->attach(RoleModel::where('code', Role::SuperAdmin->value)->firstOrCreate(['code' => Role::SuperAdmin->value], ['name' => Role::SuperAdmin->getLabel()]));
    actingAs($admin);

    get('/wms')->assertOk();

    foreach (['warehouses', 'inventory-stocks', 'stock-movements', 'inbound-receipts', 'outbound-shipments', 'stock-transfers', 'stock-adjustments'] as $slug) {
        get("/wms/{$slug}")->assertOk();
    }

    get('/wms/warehouses/create')->assertOk();
    get('/wms/inbound-receipts/create')->assertOk();
    get('/wms/outbound-shipments/create')->assertOk();
    get('/wms/stock-transfers/create')->assertOk();
    get('/wms/stock-adjustments/create')->assertOk();
});
