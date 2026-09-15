<?php

use Database\Seeders\ShopSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the storefront homepage returns a successful response', function () {
    $this->seed(ShopSeeder::class);

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('Belanja Sekarang')
        ->assertSee('Kategori Produk');
});
