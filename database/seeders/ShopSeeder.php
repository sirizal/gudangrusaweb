<?php

namespace Database\Seeders;

use App\Enums\BrandStatus;
use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShopSeeder extends Seeder
{
    /**
     * Seed the storefront with demo categories, brands, units, and products.
     */
    public function run(): void
    {
        ProductVariant::query()->forceDelete();
        ProductImage::query()->delete();
        Product::query()->forceDelete();
        Unit::query()->delete();
        Category::query()->forceDelete();
        Brand::query()->delete();

        $this->seedUnits();
        $brands = $this->seedBrands();
        $categories = $this->seedCategories();

        $this->seedProducts($brands, $categories);
    }

    private function seedUnits(): void
    {
        foreach ([
            ['name' => 'Pieces', 'code' => 'PCS', 'symbol' => 'pcs'],
            ['name' => 'Box', 'code' => 'BOX', 'symbol' => 'box'],
            ['name' => 'Set', 'code' => 'SET', 'symbol' => 'set'],
            ['name' => 'Pair', 'code' => 'PR', 'symbol' => 'pair'],
            ['name' => 'Roll', 'code' => 'RL', 'symbol' => 'roll'],
            ['name' => 'Meter', 'code' => 'M', 'symbol' => 'm'],
        ] as $unit) {
            Unit::query()->create($unit);
        }
    }

    /**
     * @return array<string, Brand>
     */
    private function seedBrands(): array
    {
        $definitions = [
            'trusco' => ['name' => 'TRUSCO', 'is_featured' => true, 'origin' => 'JP'],
            'koken' => ['name' => 'KOKEN', 'is_featured' => true, 'origin' => 'JP'],
            'midori' => ['name' => 'Midori', 'is_featured' => false, 'origin' => 'JP'],
            'bahco' => ['name' => 'Bahco', 'is_featured' => true, 'origin' => 'SE'],
            '3m' => ['name' => '3M', 'is_featured' => true, 'origin' => 'US'],
            'honeywell' => ['name' => 'Honeywell', 'is_featured' => true, 'origin' => 'US'],
            'knipex' => ['name' => 'Knipex', 'is_featured' => false, 'origin' => 'DE'],
            'stanley' => ['name' => 'Stanley', 'is_featured' => false, 'origin' => 'US'],
        ];

        $brands = [];

        foreach ($definitions as $key => $definition) {
            $brands[$key] = Brand::query()->create([
                'name' => $definition['name'],
                'slug' => $key,
                'description' => "Brand terpercaya untuk kebutuhan industri ({$definition['origin']}).",
                'logo_path' => null,
                'website' => 'https://www.gudangrusa.id',
                'country_of_origin' => $definition['origin'],
                'status' => BrandStatus::Active,
                'is_featured' => $definition['is_featured'],
            ]);
        }

        return $brands;
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $categories = [];

        $roots = [
            'perkakas-tangan' => ['name' => 'Perkakas Tangan', 'sort' => 1],
            'mro' => ['name' => 'MRO & Perawatan', 'sort' => 2],
            'alat-keselamatan' => ['name' => 'Alat Keselamatan', 'sort' => 3],
            'alat-listrik' => ['name' => 'Alat Listrik & Elektrik', 'sort' => 4],
            'atk' => ['name' => 'ATK & Kantor', 'sort' => 5],
        ];

        foreach ($roots as $slug => $root) {
            $categories[$slug] = Category::query()->create([
                'name' => $root['name'],
                'slug' => $slug,
                'code' => strtoupper(str_replace('-', '_', $slug)),
                'description' => "Kategori {$root['name']} untuk kebutuhan operasional bisnis Anda.",
                'is_active' => true,
                'sort_order' => $root['sort'],
            ]);
        }

        $children = [
            'perkakas-tangan' => [
                'obeng' => ['name' => 'Obeng & Set Obeng', 'slug' => 'obeng'],
                'tang' => ['name' => 'Tang', 'slug' => 'tang'],
                'kunci' => ['name' => 'Kunci & Socket', 'slug' => 'kunci-socket'],
            ],
            'mro' => [
                'bearing' => ['name' => 'Bearing', 'slug' => 'bearing'],
                'adhesive' => ['name' => 'Adhesive & Perekat', 'slug' => 'adhesive'],
            ],
            'alat-keselamatan' => [
                'sarung-tangan' => ['name' => 'Sarung Tangan', 'slug' => 'sarung-tangan'],
                'helm' => ['name' => 'Helm Safety', 'slug' => 'helm-safety'],
            ],
            'alat-listrik' => [
                'kabel' => ['name' => 'Kabel & Konektor', 'slug' => 'kabel'],
            ],
            'atk' => [
                'alat-tulis' => ['name' => 'Alat Tulis', 'slug' => 'alat-tulis'],
            ],
        ];

        foreach ($children as $parentSlug => $subs) {
            foreach ($subs as $key => $sub) {
                $categories[$key] = Category::query()->create([
                    'name' => $sub['name'],
                    'slug' => $sub['slug'],
                    'code' => strtoupper(str_replace('-', '_', $sub['slug'])),
                    'description' => "Kategori {$sub['name']}.",
                    'parent_id' => $categories[$parentSlug]->id,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
            }
        }

        return $categories;
    }

    /**
     * @param  array<string, Brand>  $brands
     * @param  array<string, Category>  $categories
     */
    private function seedProducts(array $brands, array $categories): void
    {
        $unit = fn (string $code) => Unit::query()->where('code', $code)->first();

        $definitions = [
            [
                'name' => 'Obeng Set 6 pcs Isi Terbuka',
                'brand' => 'bahco', 'category' => 'obeng', 'unit' => 'SET',
                'price' => 185000, 'list_price' => 220000, 'featured' => true, 'stock' => 120,
                'image' => 'https://picsum.photos/seed/obeng1/600/600',
                'variants' => [
                    ['size' => 'Set 6 pcs', 'price' => 185000, 'stock' => 120, 'leadtime' => 2, 'city' => 'Jakarta'],
                ],
            ],
            [
                'name' => 'Tang Kombinasi 8" Kualitas Jepang',
                'brand' => 'knipex', 'category' => 'tang', 'unit' => 'PCS',
                'price' => 145000, 'featured' => true, 'stock' => 60,
                'image' => 'https://picsum.photos/seed/tang1/600/600',
                'variants' => [
                    ['size' => '8 inch', 'price' => 145000, 'stock' => 60, 'leadtime' => 1, 'city' => 'Surabaya'],
                ],
            ],
            [
                'name' => 'Kunci Ring Pas Kombinasi 12 mm',
                'brand' => 'koken', 'category' => 'kunci', 'unit' => 'PCS',
                'price' => 42000, 'featured' => true, 'stock' => 200,
                'image' => 'https://picsum.photos/seed/kunci1/600/600',
                'variants' => [
                    ['size' => '12 mm', 'price' => 42000, 'stock' => 200, 'leadtime' => 1, 'city' => 'Jakarta'],
                    ['size' => '14 mm', 'price' => 45000, 'stock' => 150, 'leadtime' => 1, 'city' => 'Jakarta'],
                ],
            ],
            [
                'name' => 'Bearing Ball 6205-2RS',
                'brand' => 'trusco', 'category' => 'bearing', 'unit' => 'PCS',
                'price' => 38000, 'featured' => true, 'stock' => 400,
                'image' => 'https://picsum.photos/seed/bearing1/600/600',
                'metadata' => ['Bore' => '25 mm', 'Type' => 'Sealed'],
            ],
            [
                'name' => 'Lem Epoxy 2 Part 50 ml',
                'brand' => '3m', 'category' => 'adhesive', 'unit' => 'BOX',
                'price' => 65000, 'featured' => false, 'stock' => 90,
                'image' => 'https://picsum.photos/seed/adhesive1/600/600',
            ],
            [
                'name' => 'Sarung Tangan Safety Nitril',
                'brand' => 'honeywell', 'category' => 'sarung-tangan', 'unit' => 'PR',
                'price' => 28000, 'featured' => true, 'stock' => 500,
                'image' => 'https://picsum.photos/seed/glove1/600/600',
                'variants' => [
                    ['size' => 'L', 'price' => 28000, 'stock' => 300, 'leadtime' => 1, 'city' => 'Bandung'],
                    ['size' => 'XL', 'price' => 28000, 'stock' => 200, 'leadtime' => 1, 'city' => 'Bandung'],
                ],
            ],
            [
                'name' => 'Helm Safety Standar SNI',
                'brand' => '3m', 'category' => 'helm', 'unit' => 'PCS',
                'price' => 75000, 'featured' => false, 'stock' => 0,
                'image' => 'https://picsum.photos/seed/helm1/600/600',
            ],
            [
                'name' => 'Kabel NYM 2x1.5mm per Meter',
                'brand' => 'trusco', 'category' => 'kabel', 'unit' => 'M',
                'price' => 9500, 'featured' => false, 'stock' => 2000,
                'image' => 'https://picsum.photos/seed/kabel1/600/600',
            ],
            [
                'name' => 'Bolpoin Gel Hitam 0.5mm (Isi 12)',
                'brand' => 'midori', 'category' => 'alat-tulis', 'unit' => 'BOX',
                'price' => 45000, 'featured' => false, 'stock' => 300,
                'image' => 'https://picsum.photos/seed/pens1/600/600',
            ],
            [
                'name' => 'Obeng Kembang + Minus 3 pcs',
                'brand' => 'stanley', 'category' => 'obeng', 'unit' => 'SET',
                'price' => 52000, 'featured' => true, 'stock' => 80,
                'image' => 'https://picsum.photos/seed/obeng2/600/600',
            ],
            [
                'name' => 'Tang Potong Kabel 6"',
                'brand' => 'knipex', 'category' => 'tang', 'unit' => 'PCS',
                'price' => 165000, 'featured' => false, 'stock' => 45,
                'image' => 'https://picsum.photos/seed/tang2/600/600',
            ],
            [
                'name' => 'Set Socket Lengkap 24 pcs 1/2"',
                'brand' => 'koken', 'category' => 'kunci', 'unit' => 'SET',
                'price' => 480000, 'featured' => true, 'stock' => 25,
                'image' => 'https://picsum.photos/seed/socket1/600/600',
                'variants' => [
                    ['size' => '1/2 inch', 'price' => 480000, 'stock' => 25, 'leadtime' => 5, 'city' => 'Jakarta'],
                ],
            ],
        ];

        $units = ['PCS' => $unit('PCS'), 'BOX' => $unit('BOX'), 'SET' => $unit('SET'), 'PR' => $unit('PR'), 'M' => $unit('M')];

        foreach ($definitions as $index => $definition) {
            $product = Product::query()->create([
                'name' => $definition['name'],
                'slug' => Str::slug($definition['name']),
                'sku' => 'P-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                'description' => "{$definition['name']} — kualitas original, siap kirim ke seluruh Indonesia. Cocok untuk kebutuhan operasional dan perawatan industri.",
                'brand_id' => $brands[$definition['brand']]->id,
                'category_id' => $categories[$definition['category']]->id,
                'unit_id' => $units[$definition['unit']]->id,
                'price' => $definition['price'],
                'list_price' => $definition['list_price'] ?? null,
                'quantity_on_hand' => $definition['stock'],
                'status' => ProductStatus::Active,
                'is_featured' => $definition['featured'],
                'metadata' => $definition['metadata'] ?? null,
            ]);

            ProductImage::query()->create([
                'product_id' => $product->id,
                'image_path' => $definition['image'],
                'sort_order' => 0,
            ]);

            foreach ($definition['variants'] ?? [] as $variant) {
                ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'model_number' => null,
                    'size' => $variant['size'],
                    'color' => null,
                    'image_path' => null,
                    'unit' => 'pcs',
                    'customer_sku' => null,
                    'available_stock' => $variant['stock'],
                    'leadtime' => $variant['leadtime'] ?? null,
                    'selling_price' => $variant['price'],
                    'is_active' => true,
                    'supply_city' => $variant['city'] ?? 'Jakarta',
                ]);
            }
        }
    }
}
