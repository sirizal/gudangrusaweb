@extends('layouts.shop')

@section('title', 'Semua Produk — Gudang Rusa')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <nav class="text-sm text-neutral-500">
            <a href="{{ route('home') }}" class="hover:text-brand-600">Beranda</a>
            <span class="mx-2 text-neutral-300">/</span>
            <span class="text-neutral-800">Semua Produk</span>
        </nav>

        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Semua Produk</h1>
                <p class="mt-1 text-sm text-neutral-500">{{ $products->total() }} produk ditemukan</p>
            </div>

            <div class="flex items-center gap-2 text-sm">
                <span class="text-neutral-500">Brand:</span>
                <div class="flex flex-wrap gap-1.5">
                    <a href="{{ route('products.index', request()->except('brand')) }}"
                       class="rounded-full border border-neutral-300 px-3 py-1 text-xs font-medium text-neutral-700 {{ request()->missing('brand') ? 'border-brand-600 bg-brand-600 text-white' : 'hover:border-brand-500 hover:text-brand-600' }}">
                        Semua
                    </a>
                    @foreach ($brands as $brand)
                        <a href="{{ route('products.index', ['brand' => $brand->slug] + request()->except(['brand', 'page'])) }}"
                           class="rounded-full border border-neutral-300 px-3 py-1 text-xs font-medium text-neutral-700 {{ request('brand') === $brand->slug ? 'border-brand-600 bg-brand-600 text-white' : 'hover:border-brand-500 hover:text-brand-600' }}">
                            {{ $brand->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-6">
            @if ($products->isEmpty())
                <div class="rounded-xl border border-dashed border-neutral-300 py-20 text-center">
                    <x-heroicon-m-magnifying-glass class="mx-auto h-12 w-12 text-neutral-300" />
                    <p class="mt-4 font-medium text-neutral-700">Tidak ada produk ditemukan.</p>
                    <p class="mt-1 text-sm text-neutral-500">Coba ubah kata kunci atau filter brand Anda.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-shop.product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
