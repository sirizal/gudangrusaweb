@extends('layouts.shop')

@section('title', $category->name.' — Gudang Rusa')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8">
        <nav class="text-sm text-neutral-500">
            <a href="{{ route('home') }}" class="hover:text-brand-600">Beranda</a>
            <span class="mx-2 text-neutral-300">/</span>
            <span class="text-neutral-800">{{ $category->name }}</span>
        </nav>

        <div class="mt-4 rounded-xl bg-gradient-to-r from-neutral-900 to-neutral-700 px-6 py-8 text-white">
            <h1 class="text-2xl font-bold">{{ $category->name }}</h1>
            @if ($category->description)
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-neutral-300">{{ $category->description }}</p>
            @endif
            <p class="mt-3 text-sm text-neutral-400">{{ $products->total() }} produk</p>
        </div>

        <div class="mt-8">
            @if ($products->isEmpty())
                <div class="rounded-xl border border-dashed border-neutral-300 py-20 text-center">
                    <x-heroicon-m-tag class="mx-auto h-12 w-12 text-neutral-300" />
                    <p class="mt-4 font-medium text-neutral-700">Belum ada produk pada kategori ini.</p>
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
