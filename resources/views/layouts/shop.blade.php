<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', 'Gudang Rusa')</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white text-neutral-800 antialiased">
        {{-- Top utility bar --}}
        <div class="bg-neutral-900 text-neutral-300">
            <div class="mx-auto flex h-9 max-w-7xl items-center justify-between px-4 text-xs">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1.5">
                        <x-heroicon-m-phone class="h-3.5 w-3.5 text-brand-500" />
                        021-3110 6990
                    </span>
                    <span class="hidden items-center gap-1.5 sm:flex">
                        <x-heroicon-m-clock class="h-3.5 w-3.5 text-brand-500" />
                        Senin–Jumat 08.00–18.00 WIB
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <a href="#" class="hover:text-white">Bantuan</a>
                    <a href="#" class="hover:text-white">Masuk</a>
                    <a href="#" class="font-medium text-brand-400 hover:text-brand-300">Daftar</a>
                </div>
            </div>
        </div>

        {{-- Header --}}
        <header class="border-b border-neutral-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-4">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white">
                        <x-heroicon-m-shopping-bag class="h-5 w-5" />
                    </span>
                    <span class="text-xl font-bold tracking-tight text-neutral-900">
                        Gudang<span class="text-brand-600">Rusa</span>
                    </span>
                </a>

                <form action="{{ route('search') }}" method="GET" class="flex flex-1 items-stretch">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari perkakas, ATK, MRO & produk industri…"
                        class="w-full rounded-l-lg border border-r-0 border-neutral-300 px-4 py-2.5 text-sm outline-none focus:border-brand-500"
                    >
                    <button type="submit" class="flex items-center gap-2 rounded-r-lg bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700">
                        <x-heroicon-m-magnifying-glass class="h-4 w-4" />
                        <span class="hidden sm:inline">Cari</span>
                    </button>
                </form>

                <a href="#" class="relative flex shrink-0 items-center gap-2 rounded-lg border border-neutral-200 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:border-brand-500 hover:text-brand-600">
                    <x-heroicon-m-shopping-cart class="h-5 w-5" />
                    <span class="hidden lg:inline">Keranjang</span>
                    <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-600 px-1 text-[10px] font-bold text-white">0</span>
                </a>
            </div>

            {{-- Category nav --}}
            <nav class="border-t border-neutral-100 bg-white">
                <div class="mx-auto flex max-w-7xl items-center gap-1 px-4">
                    <a href="{{ route('products.index') }}"
                       class="flex items-center gap-1.5 px-3 py-3 text-sm font-medium text-neutral-600 hover:text-brand-600 {{ request()->routeIs('products.index') ? 'text-brand-600' : '' }}">
                        <x-heroicon-m-squares-2x2 class="h-4 w-4" />
                        Semua Produk
                    </a>

                    @foreach ($navCategories as $category)
                        <div class="group relative">
                            <a href="{{ route('categories.show', $category) }}"
                               class="flex items-center gap-1 px-3 py-3 text-sm font-medium text-neutral-600 hover:text-brand-600">
                                {{ $category->name }}
                                @if ($category->children->isNotEmpty())
                                    <x-heroicon-m-chevron-down class="h-3.5 w-3.5 text-neutral-400" />
                                @endif
                            </a>

                            @if ($category->children->isNotEmpty())
                                <div class="invisible absolute left-0 top-full z-30 w-64 rounded-b-lg border border-neutral-100 bg-white p-2 opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100">
                                    @foreach ($category->children as $child)
                                        <a href="{{ route('categories.show', $child) }}"
                                           class="block rounded-md px-3 py-2 text-sm text-neutral-600 hover:bg-neutral-50 hover:text-brand-600">
                                            {{ $child->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </nav>
        </header>

        <main>
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="mt-16 bg-neutral-900 text-neutral-300">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-12 md:grid-cols-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white">
                            <x-heroicon-m-shopping-bag class="h-4 w-4" />
                        </span>
                        <span class="text-lg font-bold text-white">
                            Gudang<span class="text-brand-500">Rusa</span>
                        </span>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed text-neutral-400">
                        Solusi pengadaan perkakas industri, MRO, dan ATK untuk kebutuhan bisnis Anda.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-white">Belanja</h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="{{ route('products.index') }}" class="hover:text-white">Semua Produk</a></li>
                        <li><a href="{{ route('search') }}" class="hover:text-white">Pencarian</a></li>
                        <li><a href="#" class="hover:text-white">Promo</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-white">Layanan</h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white">Cara Belanja</a></li>
                        <li><a href="#" class="hover:text-white">Pengiriman</a></li>
                        <li><a href="#" class="hover:text-white">Hubungi Kami</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-white">Kontak</h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li class="flex items-center gap-2"><x-heroicon-m-phone class="h-4 w-4 text-brand-500" /> 021-3110 6990</li>
                        <li class="flex items-center gap-2"><x-heroicon-m-envelope class="h-4 w-4 text-brand-500" /> cs@gudangrusa.id</li>
                        <li class="flex items-center gap-2"><x-heroicon-m-map-pin class="h-4 w-4 text-brand-500" /> Jakarta, Indonesia</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-neutral-800">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-5 text-xs text-neutral-500">
                    <span>© {{ date('Y') }} Gudang Rusa. Hak cipta dilindungi.</span>
                    <span>Perkakas Industri & ATK</span>
                </div>
            </div>
        </footer>
    </body>
</html>
