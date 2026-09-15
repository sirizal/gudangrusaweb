---
paths:
  - app/Http/Controllers/ShopController.php
---

# Controllers

## Storefront controller/route conventions
The Blade storefront is served by ShopController (home/index/show/category/search) with views in resources/views/shop/**. The layout resources/views/layouts/shop.blade.php gets navCategories via a View::composer in AppServiceProvider (root categories + active children). Only Product::active() (status = ProductStatus::Active) products are shown; Product::show aborts 404 for non-active. Price display uses number_format IDR.
