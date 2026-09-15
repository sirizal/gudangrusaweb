@extends('layouts.shop')

@section('title', 'Gudang Rusa — Perkakas Industri & ATK')

@section('content')
    {{-- Hero --}}
    <section class="bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 text-white">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-8 px-4 py-14 lg:grid-cols-2 lg:py-20">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-brand-100">One-Stop Solution</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight lg:text-5xl">
                    Kebutuhan Industri & ATK Bisnis Anda, Semua Ada di Sini
                </h1>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-brand-50">
                    Ribuan produk perkakas, MRO, alat keselamatan, dan ATK dengan harga bersaing,
                    pengiriman cepat, dan dukungan produk profesional.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('products.index') }}"
                       class="rounded-lg bg-white px-6 py-3 text-sm font-semibold text-brand-700 shadow hover:bg-brand-50">
                        Belanja Sekarang
                    </a>
                    <a href="{{ route('search') }}"
                       class="rounded-lg border border-white/40 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10">
                        Cari Produk
                    </a>
                </div>
            </div>
            <div class="hidden justify-center lg:flex">
                <span class="flex h-56 w-56 items-center justify-center rounded-full bg-white/10">
                    <x-heroicon-m-wrench-screwdriver class="h-32 w-32 text-white/80" />
                </span>
            </div>
        </div>
    </section>

    {{-- Category tiles --}}
    <section class="mx-auto max-w-7xl px-4 py-12">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-neutral-900">Kategori Produk</h2>
            <a href="{{ route('products.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                Lihat Semua →
            </a>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @forelse ($categories as $category)
                <a href="{{ route('categories.show', $category) }}"
                   class="group flex flex-col items-center gap-3 rounded-xl border border-neutral-200 bg-white p-6 text-center transition hover:border-brand-500 hover:shadow-md">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-600 transition group-hover:bg-brand-600 group-hover:text-white">
                        <x-heroicon-m-tag class="h-7 w-7" />
                    </span>
                    <span class="text-sm font-semibold text-neutral-800 group-hover:text-brand-600">{{ $category->name }}</span>
                    <span class="text-xs text-neutral-400">{{ $category->products_count }} produk</span>
                </a>
            @empty
                <p class="col-span-full text-center text-neutral-400">Belum ada kategori.</p>
            @endforelse
        </div>
    </section>

    {{-- Featured products --}}
    <section class="bg-neutral-50 py-12">
        <div class="mx-auto max-w-7xl px-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-neutral-900">Produk Pilihan</h2>
                <a href="{{ route('products.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                    Lihat Semua →
                </a>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @forelse ($featuredProducts as $product)
                    <x-shop.product-card :product="$product" />
                @empty
                    <p class="col-span-full text-center text-neutral-400">Belum ada produk pilihan.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Benefits strip --}}
    <section class="mx-auto max-w-7xl px-4 py-12">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $benefits = [
                    ['icon' => 'shipping', 'title' => 'Pengiriman Cepat', 'desc' => 'Jabodetabek & area industri'],
                    ['icon' => 'cube', 'title' => 'Stok Lengkap', 'desc' => 'Ribuan produk siap kirim'],
                    ['icon' => 'shield', 'title' => 'Produk Original', 'desc' => 'Dijamin kualitas dan garansi'],
                    ['icon' => 'support', 'title' => 'Dukungan Produk', 'desc' => 'Product advisor siap membantu'],
                ];
            @endphp

            @foreach ($benefits as $benefit)
                <div class="flex items-start gap-4 rounded-xl border border-neutral-200 bg-white p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        @if ($benefit['icon'] === 'shipping')
                            <x-heroicon-m-truck class="h-6 w-6" />
                        @elseif ($benefit['icon'] === 'cube')
                            <x-heroicon-m-cube class="h-6 w-6" />
                        @elseif ($benefit['icon'] === 'shield')
                            <x-heroicon-m-shield-check class="h-6 w-6" />
                        @else
                            <x-heroicon-m-lifebuoy class="h-6 w-6" />
                        @endif
                    </span>
                    <div>
                        <p class="font-semibold text-neutral-900">{{ $benefit['title'] }}</p>
                        <p class="mt-0.5 text-sm text-neutral-500">{{ $benefit['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Brands --}}
    @if ($brands->isNotEmpty())
        <section class="border-t border-neutral-100 py-12">
            <div class="mx-auto max-w-7xl px-4">
                <h2 class="text-center text-xl font-bold text-neutral-900">Brand Unggulan</h2>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-6">
                    @foreach ($brands as $brand)
                        <div class="flex items-center gap-2 text-neutral-500 hover:text-brand-600">
                            @if ($brand->logo_path)
                                <img src="{{ \App\Support\Shop::imageUrl($brand->logo_path) }}" alt="{{ $brand->name }}" class="h-8 w-auto">
                            @else
                                <span class="text-lg font-bold">{{ $brand->name }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
