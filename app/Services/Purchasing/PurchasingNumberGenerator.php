<?php

namespace App\Services\Purchasing;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Models\VendorPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchasingNumberGenerator
{
    public function nextVendorCode(): string
    {
        return $this->next('vendors', 'vendor_code', 'VEND-', Vendor::class, 4);
    }

    public function nextRequestCode(?\DateTimeInterface $date = null): string
    {
        return $this->nextYearly('purchase_requests', 'request_code', 'PR', $date, PurchaseRequest::class);
    }

    public function nextOrderCode(?\DateTimeInterface $date = null): string
    {
        return $this->nextYearly('purchase_orders', 'po_code', 'PO', $date, PurchaseOrder::class);
    }

    public function nextReceiptCode(?\DateTimeInterface $date = null): string
    {
        return $this->nextYearly('goods_receipts', 'receipt_code', 'GR', $date, GoodsReceipt::class);
    }

    public function nextBillCode(?\DateTimeInterface $date = null): string
    {
        return $this->nextYearly('vendor_bills', 'bill_code', 'VB', $date, VendorBill::class);
    }

    public function nextPaymentCode(?\DateTimeInterface $date = null): string
    {
        return $this->nextYearly('vendor_payments', 'payment_code', 'PV', $date, VendorPayment::class);
    }

    /**
     * @param  class-string  $model
     */
    protected function next(string $table, string $column, string $prefix, string $model, int $pad): string
    {
        $last = $this->query($model)
            ->where($column, 'like', $prefix.'%')
            ->orderByDesc($column)
            ->value($column);

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, $pad, '0', STR_PAD_LEFT);
    }

    /**
     * @param  class-string  $model
     */
    protected function nextYearly(string $table, string $column, string $code, ?\DateTimeInterface $date, string $model): string
    {
        $year = $date ? $date->format('Y') : now()->format('Y');
        $prefix = $code.'-'.$year.'-';

        $last = $this->query($model)
            ->where($column, 'like', $prefix.'%')
            ->orderByDesc($column)
            ->value($column);

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  class-string  $model
     */
    protected function query(string $model): Builder
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true)
            ? $model::withTrashed()
            : $model::query();
    }
}
