---
paths:
  - 'app/Services/Purchasing/**'
  - app/Services/Purchasing/PurchasingNumberGenerator.php
---

# Purchasing

## Purchasing procure-to-pay flow + GRNI journal posting
Purchasing (procure-to-pay) flow: Purchase Request (PR-YYYY-####) -> Purchase Order (PO-YYYY-####) -> Goods Receipt (GR-YYYY-####, posts Dr expense/asset account + Cr GRNI) -> Vendor Bill (VB-YYYY-####, posts Dr GRNI net + Dr input VAT PPN-IN + Cr AP) -> Vendor Payment (PV-YYYY-####, posts Dr AP + Cr Cash, allocates to bills). Inventory purchase lines are received by the (future) warehouse, NOT here — GoodsReceiptService rejects inventory lines. Use PurchaseType enum: inventory (product_id), general (expense account_id), capex (asset account_id). Journal posting lives in the PostsPurchasingJournals trait (resolveAccount via statement lines BS-GRNI/BS-AP/BS-CASH + parent_id descendant walk, resolvePeriod, postJournal). Seeded GRNI account 2150 + BS-GRNI statement line.

## Purchasing number generator soft-delete guard + panel access
PurchasingNumberGenerator uses a query() helper that calls withTrashed() only when the model uses SoftDeletes — GoodsReceipt and VendorPayment tables have no deleted_at, so unconditionally calling withTrashed() throws. Purchasing panel id 'purchasing' path '/purchasing' is gated to SuperAdmin/FinanceManager/Accountant/Purchasing (new Role::Purchasing enum case).
