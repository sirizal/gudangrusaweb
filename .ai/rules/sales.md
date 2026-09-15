---
paths:
  - 'app/Services/Sales/**'
  - app/Services/Sales/SalesInvoiceService.php
---

# Sales

## Sales services: codes, order workflow, invoice + journal posting
Sales panel services: SalesNumberGenerator auto-generates customer (CUST-0001), order (SO-YYYY-0001) and invoice (INV-YYYY-0001) codes. SalesOrderService creates/updates orders with lines (recomputes subtotal/total) and advances status raised->open->on_pick->on_pack->on_delivery->delivered (SalesOrderStatus::next()) or cancels; reaching delivered auto-issues an invoice. SalesInvoiceService issueFromOrder computes subtotal/tax (PPN via TaxCode rate when customer is_pkp)/total, due_date = invoice_date + payment_term.due_days, and posts a Posted journal (source sales_invoice, reference = Invoice) — Dr AR (BS-AR), Cr Revenue (IS-REV), Cr Tax (BS-TAX). recordPayment posts Dr Cash (BS-CASH)/Cr AR and updates paid_amount/status. cancel() reverses the journal via AccountingService::reverse.

## Resolve statement-line accounts via parent_id chain, not code prefix
When resolving an account from a financial statement line, do NOT use account_code LIKE prefix matching — account codes are not prefix-nested (1110's postable children are 1111/1112, which do not start with '1110'). Instead walk the parent_id chain: load the company's accounts, build an id map, and find the first postable+active account whose ancestor chain includes the mapped group account.
