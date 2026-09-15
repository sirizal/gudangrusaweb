<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAs(User::factory()->create());
});

it('renders the admin main dashboard with quick links to the other panels', function () {
    get('/admin/main-dashboard')
        ->assertOk()
        ->assertSee('Products')
        ->assertSee('Accounting')
        ->assertSee('Geography');
});

it('shows only panels the user can access', function () {
    $response = get('/admin/main-dashboard')->assertOk();

    expect($response->getContent())->toContain('href="/catalog"')
        ->and($response->getContent())->toContain('href="/accounting"')
        ->and($response->getContent())->toContain('href="/geography"');
});
