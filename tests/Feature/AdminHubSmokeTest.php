<?php

use App\Enums\Role;
use App\Models\Role as RoleModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows only the panels a plain user can access', function () {
    actingAs(User::factory()->create());

    $response = get('/admin/main-dashboard')->assertOk();
    $html = $response->getContent();

    // Products is open to everyone; other panels are role-gated.
    expect($html)->toContain('href="/catalog"')
        ->and($html)->not->toContain('href="/accounting"')
        ->and($html)->not->toContain('href="/sales"')
        ->and($html)->not->toContain('href="/geography"');
});

it('shows every panel to a super admin', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(
        RoleModel::where('code', Role::SuperAdmin->value)->firstOrCreate(
            ['code' => Role::SuperAdmin->value],
            ['name' => Role::SuperAdmin->getLabel()],
        ),
    );

    actingAs($admin);

    get('/admin/main-dashboard')
        ->assertOk()
        ->assertSee('Products')
        ->assertSee('Accounting')
        ->assertSee('Sales')
        ->assertSee('Geography');
});
