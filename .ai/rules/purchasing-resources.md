---
paths:
  - 'app/Filament/Purchasing/Resources/**'
---

# Purchasing Resources

## Purchase line repeater: conditional visibility + view-page hydration
In purchase line repeaters, conditional field visibility based on a sibling's default (purchase_type) does NOT resolve on the create form's initial render — $get('purchase_type') returns a non-'inventory' value at schema-build time, so both the product and account selects stayed hidden. Fix: make product visible unless purchase_type is general/capex (i.e. ! in_array($get('purchase_type'), [general, capex], true)), and account visible only for general/capex. Selecting an inventory product auto-fills unit_price (product.price) + description (product.name) via ->live()->afterStateUpdated. Also: plain-state repeaters are NOT hydrated on View pages automatically — Edit AND View pages must use a HydratesXxxLines trait (mutateFormDataBeforeFill) or the lines render empty; EditPurchaseRequest/EditVendorBill also OVERRIDE handleRecordUpdate to call the service (not the default update), otherwise lines/totals aren't persisted.

## Enum Select state returns the enum in Get closures (purchase line toggle)
For Select fields using ->options(SomeEnum::class), the closure Get utility returns the ENUM INSTANCE (e.g. App\Enums\PurchaseType), not the string value — so comparisons like $get('purchase_type') === 'general' silently fail, which made the Product/Account line selects never toggle. Normalize with helper methods (PurchaseRequestForm::isAccountType()/isCapex()) that accept PurchaseType|string|null and compare ->value; accountOptions() also accepts the enum now. Product/account selects are ->columnSpan(2) in the 6-column line repeater. Purchase line repeaters must be hydrated on BOTH Edit and View pages via the HydratesXxxLines traits, and Edit pages override handleRecordUpdate to call the service.
