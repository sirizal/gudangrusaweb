<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

class ShopController extends Controller
{
    public function home()
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->root()
            ->withCount(['products' => fn ($query) => $query->active()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $featuredProducts = Product::query()
            ->active()
            ->featured()
            ->with(['brand', 'category', 'images'])
            ->latest()
            ->take(8)
            ->get();

        $brands = Brand::query()
            ->active()
            ->featured()
            ->take(6)
            ->get();

        return view('shop.home', compact('categories', 'featuredProducts', 'brands'));
    }

    public function index()
    {
        $products = Product::query()
            ->active()
            ->with(['brand', 'category', 'images'])
            ->when(request('q'), fn ($query, string $q) => $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            }))
            ->when(request('brand'), fn ($query, string $brand) => $query->whereHas(
                'brand',
                fn ($query) => $query->where('slug', $brand)
            ))
            ->when(request('category'), fn ($query, string $category) => $query->whereHas(
                'category',
                fn ($query) => $query->where('slug', $category)
            ))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $brands = Brand::query()->active()->orderBy('name')->get();

        return view('shop.products.index', compact('products', 'brands'));
    }

    public function show(Product $product)
    {
        abort_unless($product->status === ProductStatus::Active, 404);

        $product->load(['brand', 'category', 'unit', 'images', 'variants' => fn ($query) => $query->where('is_active', true)]);

        return view('shop.products.show', compact('product'));
    }

    public function category(Category $category)
    {
        $categoryIds = $this->categoryAndDescendantIds($category);

        $products = Product::query()
            ->active()
            ->whereIn('category_id', $categoryIds)
            ->with(['brand', 'images'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('shop.categories.show', compact('category', 'products'));
    }

    public function search()
    {
        $q = trim((string) request('q'));

        $products = collect();

        if ($q !== '') {
            $products = Product::query()
                ->active()
                ->with(['brand', 'images'])
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                })
                ->latest()
                ->paginate(12)
                ->withQueryString();
        }

        return view('shop.search', compact('products', 'q'));
    }

    /**
     * Return the category id and the ids of all of its descendants.
     *
     * @return array<int, int>
     */
    private function categoryAndDescendantIds(Category $category): array
    {
        $ids = [$category->id];
        $children = Category::query()->where('parent_id', $category->id)->get();

        foreach ($children as $child) {
            $ids = array_merge($ids, $this->categoryAndDescendantIds($child));
        }

        return $ids;
    }
}
