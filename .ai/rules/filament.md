---
paths:
  - 'app/Filament/**/*.php'
---

# Filament

## Table columns on JSON fields receive raw JSON string in formatStateUsing
Filament TextColumn::formatStateUsing passes the raw state for JSON columns (a string, not the cast array). Type-hint the closure as mixed and json_decode strings before inspecting; do not type-hint `?array $state`.

## Filament uploads must use the public disk for storefront images
Storefront images are served from the public disk at /storage (via the public/storage symlink and App\\Support\\Shop::imageUrl). Filament FileUpload fields MUST set ->disk('public') (ProductForm image_path, VariantsRelationManager image_path, BrandForm logo_path, CategoryForm logo_path). Without it, files land in the local/private disk (storage/app/private) because FILESYSTEM_DISK=local, and storefront images 404/403. Also re-check Shop::imageUrl name and public disk existence in product detail.
