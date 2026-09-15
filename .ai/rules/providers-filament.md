---
paths:
  - app/Providers/Filament/AccountingPanelProvider.php
  - app/Providers/Filament/ProductsPanelProvider.php
---

# Providers Filament

## Custom Filament pages need Vite theme for Tailwind utilities
The accounting panel MUST register ->viteTheme('resources/css/filament/accounting/theme.css'). Filament v5 serves its pre-compiled CSS by default and will not apply custom Tailwind utilities used inside custom page Blade views unless this Vite theme is registered AND the theme.css @source list covers the view directory. Report blades live in resources/views/filament/pages/accounting/** — theme.css must @source that path. Rebuild with `npm run build` after editing report views.

## Products panel id vs path (/catalog) collision with storefront
The product-management panel has id 'products' but path '/catalog' because the public storefront route '/products' (ShopController) occupies that path — a Filament panel at /products would be shadowed by the web route. Panel is open to any authenticated user (User::canAccessPanel default true). Resources live at app/Filament/Products/Resources/{Brands,Categories,Products,Units} with namespace App\Filament\Products\Resources. Test product resource pages with filament()->setCurrentPanel('products').
