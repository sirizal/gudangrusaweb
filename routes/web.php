<?php

use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/products', [ShopController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ShopController::class, 'show'])->name('products.show');
Route::get('/categories/{category:slug}', [ShopController::class, 'category'])->name('categories.show');
Route::get('/search', [ShopController::class, 'search'])->name('search');
