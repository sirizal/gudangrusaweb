@props(['product'])

<div class="group relative flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition hover:border-neutral-300 hover:shadow-md">
    <a href="{{ route('products.show', $product) }}" class="relative flex aspect-square items-center justify-center overflow-hidden bg-neutral-100">
        @php($image = $product->images->first()?->image_path)
        @if ($image && \App\Support\Shop::imageUrl($image))
            <img
                src="{{ \App\Support\Shop::imageUrl($image) }}"
                alt="{{ $product->name }}"
                loading="lazy"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
            >
        @else
            <x-heroicon-m-photo class="h-12 w-12 text-neutral-300" />
        @endif

        @if ($product->quantity_on_hand <= 0)
            <span class="absolute left-2 top-2 rounded-full bg-neutral-800/80 px-2.5 py-1 text-[11px] font-semibold text-white">
                Habis
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        @if ($product->brand)
            <p class="text-xs font-medium uppercase tracking-wide text-neutral-400">{{ $product->brand->name }}</p>
        @endif

        <a href="{{ route('products.show', $product) }}" class="mt-1 line-clamp-2 text-sm font-medium leading-snug text-neutral-800 hover:text-brand-600">
            {{ $product->name }}
        </a>

        <p class="mt-2 text-lg font-bold text-brand-600">
            Rp {{ number_format((float) $product->price, 0, ',', '.') }}
        </p>

        <div class="mt-3 flex items-center justify-between">
            <span class="text-xs text-neutral-400">SKU: {{ $product->sku }}</span>
            <a href="{{ route('products.show', $product) }}" class="rounded-lg bg-brand-600 p-2 text-white transition hover:bg-brand-700" title="Lihat detail">
                <x-heroicon-m-shopping-cart class="h-4 w-4" />
            </a>
        </div>
    </div>
</div>
