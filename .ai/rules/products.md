---
paths:
  - app/Services/Products/CatalogImportService.php
---

# Products

## Catalog import service semantics (unit/brand/category/product)
CatalogImportService::bulkValidate(path, type) + bulkCommit(rows, type) imports units, brands, categories, products from CSV/XLSX. Types: unit (name/code/symbol), brand (name auto-slug, website, country_of_origin, status, is_featured), category (name auto-slug, code, unspsc, parent_code/parent_name, is_active, sort_order), product (name auto-slug, sku, brand/category/unit by name-or-code, price, list_price, quantity_on_hand, status). Commit uses firstOrCreate on natural keys (unit.code, brand.slug, category.code, product.sku) — idempotent, no SQLite partial-index issue. References are resolved from preloaded maps (no per-row queries). ImportCatalog page (app/Filament/Products/Pages/ImportCatalog) at /catalog/import-catalog stores raw valid rows in $validRows for commit and a merged display array in $previewRows for the table.
