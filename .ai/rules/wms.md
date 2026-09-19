---
paths:
  - 'app/Services/Wms/**'
---

# Wms

## WMS FIFO inbound/outbound/transfer/adjustment + journal posting
WMS uses FIFO inventory layers. Inbound (InboundService::receive) receives PO inventory lines into a warehouse location: creates an InventoryLayer, increases stock, updates the PO line received_quantity + PO status, and posts Dr Inventory (BS-INV)/Cr GRNI (BS-GRNI). Outbound (OutboundService::ship) is MANUAL against a DELIVERED sales order: FIFO-consumes layers (oldest received_date), posts Dr COGS (IS-COS)/Cr Inventory (BS-INV). Stock transfer preserves FIFO cost (consume source layers -> recreate at destination). Adjustment posts gain/loss vs IS-OI/IS-OE. FifoService.consume() only decrements layer.quantity_remaining — the caller must also call FifoService.stock(-qty); FifoService.receive() DOES call stock(+qty) internally, so do NOT also decrement/increment stock around receive (double-count bug). Stock is per (product, location); Product.quantity_on_hand is the aggregate kept in sync.
