---
paths:
  - 'resources/views/**'
---

# Views

## Storefront image URLs and brand palette
Render stored image paths (products/..., products/variants/...) with App\\Support\\Shop::imageUrl($path). It returns external http(s) URLs unchanged and resolves storage paths via Storage::disk('public')->url(). Filament uploads go to the public disk; run `php artisan storage:link` in dev. Brand colors: Tailwind `brand-*` palette defined in resources/css/app.css @theme.
