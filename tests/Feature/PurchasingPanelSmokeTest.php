<?php

use App\Enums\Role;
use App\Models\Role as RoleModel;
use App\Models\User;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\PurchasingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);
    $this->seed(PurchasingSeeder::class);
});

it('allows accounting and purchasing roles into the purchasing panel and denies others', function () {
    $purchasing = User::factory()->create();
    $purchasing->roles()->attach(
        RoleModel::where('code', Role::Purchasing->value)->firstOrCreate(
            ['code' => Role::Purchasing->value],
            ['name' => Role::Purchasing->getLabel()],
        ),
    );

    expect($purchasing->canAccessPanel(filament()->getPanel('purchasing')))->toBeTrue();

    $viewer = User::factory()->create();
    $viewer->roles()->attach(
        RoleModel::where('code', Role::Viewer->value)->firstOrCreate(
            ['code' => Role::Viewer->value],
            ['name' => Role::Viewer->getLabel()],
        ),
    );

    expect($viewer->canAccessPanel(filament()->getPanel('purchasing')))->toBeFalse();
});

it('renders the purchasing dashboard and every resource page', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(
        RoleModel::where('code', Role::SuperAdmin->value)->firstOrCreate(
            ['code' => Role::SuperAdmin->value],
            ['name' => Role::SuperAdmin->getLabel()],
        ),
    );

    actingAs($admin);

    get('/purchasing')->assertOk();

    foreach ([
        'vendors',
        'purchase-requests',
        'purchase-orders',
        'goods-receipts',
        'vendor-bills',
        'vendor-payments',
    ] as $slug) {
        get("/purchasing/{$slug}")->assertOk();
    }

    get('/purchasing/vendors/create')->assertOk();
    get('/purchasing/purchase-requests/create')->assertOk();
    get('/purchasing/purchase-orders/create')->assertOk();
    get('/purchasing/vendor-bills/create')->assertOk();
    get('/purchasing/vendor-payments/create')->assertOk();
});
