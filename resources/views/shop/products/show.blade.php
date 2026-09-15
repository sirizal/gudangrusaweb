@extends('layouts.shop')

@section('title', $product->name.' — Gudang Rusa')

@section('content')
    @php
        $gallery = $product->images->pluck('image_path')->all();
        $variantImage = $product->variants->first(fn ($v) => $v->image_path)?->image_path;
        if ($variantImage && ! in_array($variantImage, $gallery, true)) {
            $gallery[] = $variantImage;
        }
        $mainImage = $gallery[0] ?? null;

        $variants = $product->variants;
        $showVariantImage = $variants->contains(fn ($v) => $v->image_path);
        $showVariantSpec = $variants->contains(fn ($v) => $v->size || $v->color || $v->model_number);
        $showUnit = $variants->contains(fn ($v) => $v->unit);
        $showCustomerSku = $variants->contains(fn ($v) => $v->customer_sku);
        $showLeadtime = $variants->contains(fn ($v) => $v->available_stock <= 0 && $v->leadtime);
        $showSupplyCity = $variants->contains(fn ($v) => $v->supply_city);
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-8">
        <nav class="text-sm text-neutral-500">
            <a href="{{ route('home') }}" class="hover:text-brand-600">Beranda</a>
            <span class="mx-2 text-neutral-300">/</span>
            @if ($product->category)
                <a href="{{ route('categories.show', $product->category) }}" class="hover:text-brand-600">{{ $product->category->name }}</a>
                <span class="mx-2 text-neutral-300">/</span>
            @endif
            <span class="text-neutral-800">{{ Str::limit($product->name, 40) }}</span>
        </nav>

        <div class="mt-6 grid grid-cols-1 gap-10 lg:grid-cols-2">
            {{-- Gallery --}}
            <div class="lg:max-w-sm">
                <div class="overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50">
                    @if ($mainImage && \App\Support\Shop::imageUrl($mainImage))
                        <img src="{{ \App\Support\Shop::imageUrl($mainImage) }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
                    @else
                        <div class="flex aspect-square items-center justify-center">
                            <x-heroicon-m-photo class="h-16 w-16 text-neutral-300" />
                        </div>
                    @endif
                </div>

                @if (count($gallery) > 1)
                    <div class="mt-3 flex gap-3 overflow-x-auto">
                        @foreach ($gallery as $image)
                            <img src="{{ \App\Support\Shop::imageUrl($image) }}" alt="{{ $product->name }}"
                                 class="h-20 w-20 shrink-0 cursor-pointer rounded-lg border border-neutral-200 object-cover hover:border-brand-500">
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Details --}}
            <div>
                @if ($product->brand)
                    <p class="text-sm font-semibold uppercase tracking-wide text-brand-600">{{ $product->brand->name }}</p>
                @endif

                <h1 class="mt-2 text-2xl font-bold leading-tight text-neutral-900">{{ $product->name }}</h1>

                <div class="mt-3 text-sm">
                    <span class="text-neutral-500">SKU: {{ $product->sku }}</span>
                </div>

                {{-- Description --}}
                @if ($product->description)
                    <div class="mt-7">
                        <h2 class="text-lg font-bold text-neutral-900">Deskripsi Produk</h2>
                        <div class="prose mt-4 max-w-none text-sm leading-relaxed text-neutral-600">
                            {{ $product->description }}
                        </div>
                    </div>
                @endif

                {{-- Specifications --}}
                @if (! empty($product->metadata))
                    <div class="mt-7">
                        <h2 class="text-lg font-bold text-neutral-900">Spesifikasi</h2>
                        <dl class="mt-4 divide-y divide-neutral-100 rounded-xl border border-neutral-200">
                            @foreach ($product->metadata as $key => $value)
                                <div class="flex justify-between px-4 py-3 text-sm">
                                    <dt class="text-neutral-500">{{ Str::title(str_replace('_', ' ', $key)) }}</dt>
                                    <dd class="font-medium text-neutral-800">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>
        </div>

        {{-- Variants --}}
        @if ($product->variants->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-lg font-bold text-neutral-900">Daftar Varian</h2>
                <div class="mt-4 overflow-x-auto rounded-xl border border-neutral-200">
                    <table class="w-full min-w-[680px] text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                @if ($showVariantImage)
                                    <th class="px-4 py-3">Gambar</th>
                                @endif
                                <th class="px-4 py-3">SKU</th>
                                @if ($showVariantSpec)
                                    <th class="px-4 py-3">Spesifikasi</th>
                                @endif
                                @if ($showUnit)
                                    <th class="px-4 py-3">Unit</th>
                                @endif
                                @if ($showCustomerSku)
                                    <th class="px-4 py-3">Customer SKU</th>
                                @endif
                                <th class="px-4 py-3 text-right">Harga</th>
                                <th class="px-4 py-3">Stok</th>
                                @if ($showLeadtime)
                                    <th class="px-4 py-3">Lead Time</th>
                                @endif
                                @if ($showSupplyCity)
                                    <th class="px-4 py-3">Kota Supply</th>
                                @endif
                                <th class="px-4 py-3">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($variants as $variant)
                                <tr class="transition hover:bg-brand-50/50">
                                    @if ($showVariantImage)
                                        <td class="px-4 py-3">
                                            @if ($variant->image_path && \App\Support\Shop::imageUrl($variant->image_path))
                                                <img src="{{ \App\Support\Shop::imageUrl($variant->image_path) }}" alt="{{ $variant->sku }}"
                                                     class="h-14 w-14 rounded-lg border border-neutral-200 object-cover">
                                            @else
                                                <div class="flex h-14 w-14 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50">
                                                    <x-heroicon-m-photo class="h-6 w-6 text-neutral-300" />
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-4 py-3 font-medium text-neutral-800">{{ $variant->sku }}</td>
                                    @if ($showVariantSpec)
                                        <td class="px-4 py-3 text-neutral-600">
                                            {{ collect([$variant->size, $variant->color, $variant->model_number])->filter()->join(' · ') ?: '—' }}
                                        </td>
                                    @endif
                                    @if ($showUnit)
                                        <td class="px-4 py-3 text-neutral-600">{{ $variant->unit ?: '—' }}</td>
                                    @endif
                                    @if ($showCustomerSku)
                                        <td class="px-4 py-3 text-neutral-600">{{ $variant->customer_sku ?: '—' }}</td>
                                    @endif
                                    <td class="px-4 py-3 text-right font-semibold text-brand-600">
                                        Rp {{ number_format((float) $variant->selling_price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($variant->available_stock > 0)
                                            <span class="rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-600">Ready</span>
                                        @else
                                            <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-600">Habis</span>
                                        @endif
                                    </td>
                                    @if ($showLeadtime)
                                        <td class="px-4 py-3 text-neutral-600">
                                            @if ($variant->available_stock <= 0 && $variant->leadtime)
                                                {{ $variant->leadtime.' hari' }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endif
                                    @if ($showSupplyCity)
                                        <td class="px-4 py-3 text-neutral-600">{{ $variant->supply_city ?: '—' }}</td>
                                    @endif
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <input type="number" min="1" value="1"
                                                   class="w-16 rounded-lg border border-neutral-300 px-2 py-1.5 text-center text-sm outline-none focus:border-brand-500">
                                            <button type="button"
                                                    class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                                                Beli
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        </div>
@endsection
