@extends('layouts.shop')

@section('title', 'Pencarian — Gudang Rusa')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <h1 class="text-2xl font-bold text-neutral-900">Pencarian Produk</h1>

        <form action="{{ route('search') }}" method="GET" class="mt-4 flex max-w-2xl items-stretch">
            <input
                type="text"
                name="q"
                value="{{ $q }}"
                placeholder="Cari nama produk, SKU, atau deskripsi…"
                class="w-full rounded-l-lg border border-r-0 border-neutral-300 px-4 py-2.5 text-sm outline-none focus:border-brand-500"
            >
            <button type="submit" class="flex items-center gap-2 rounded-r-lg bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700">
                <x-heroicon-m-magnifying-glass class="h-4 w-4" />
                Cari
            </button>
        </form>

        <div class="mt-8">
            @if ($q === '')
                <div class="rounded-xl border border-dashed border-neutral-300 py-20 text-center">
                    <x-heroicon-m-magnifying-glass class="mx-auto h-12 w-12 text-neutral-300" />
                    <p class="mt-4 font-medium text-neutral-700">Masukkan kata kunci untuk mencari produk.</p>
                </div>
            @elseif ($products->isEmpty())
                <div class="rounded-xl border border-dashed border-neutral-300 py-20 text-center">
                    <x-heroicon-m-magnifying-glass class="mx-auto h-12 w-12 text-neutral-300" />
                    <p class="mt-4 font-medium text-neutral-700">Tidak ada hasil untuk "<span class="text-brand-600">{{ $q }}</span>".</p>
                    <p class="mt-1 text-sm text-neutral-500">Coba kata kunci lain atau telusuri seluruh produk.</p>
                </div>
            @else
                <p class="text-sm text-neutral-500">{{ $products->total() }} hasil untuk "<span class="font-medium text-neutral-800">{{ $q }}</span>"</p>

                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
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
