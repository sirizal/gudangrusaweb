<?php

use App\Enums\Role;
use App\Models\Role as RoleModel;
use App\Models\User;
use Database\Seeders\SalesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SalesSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(
        RoleModel::where('code', Role::SuperAdmin->value)->firstOrCreate(
            ['code' => Role::SuperAdmin->value],
            ['name' => Role::SuperAdmin->getLabel()],
        ),
    );

    actingAs($this->admin);
});

it('allows accounting staff into the sales panel and denies others', function () {
    expect($this->admin->canAccessPanel(filament()->getPanel('sales')))->toBeTrue();

    $viewer = User::factory()->create();
    $viewer->roles()->attach(
        RoleModel::where('code', Role::Viewer->value)->firstOrCreate(
            ['code' => Role::Viewer->value],
            ['name' => Role::Viewer->getLabel()],
        ),
    );

    expect($viewer->canAccessPanel(filament()->getPanel('sales')))->toBeFalse();
});

it('renders the sales dashboard and every resource page', function () {
    get('/sales')->assertOk();

    foreach ([
        'customers',
        'sales-orders',
        'invoices',
    ] as $slug) {
        get("/sales/{$slug}")->assertOk();
    }

    get('/sales/customers/create')->assertOk();
    get('/sales/sales-orders/create')->assertOk();
});
