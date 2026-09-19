<?php

namespace App\Services\Wms;

use App\Models\InboundReceipt;
use App\Models\OutboundShipment;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class WmsNumberGenerator
{
    public function nextWarehouseCode(): string
    {
        return $this->next('WH-', 4, Warehouse::class, 'warehouse_code');
    }

    public function nextInboundCode(?\DateTimeInterface $date = null): string
    {
        return $this->nextYearly('WGR', $date, InboundReceipt::class, 'receipt_code');
    }

    public function nextOutboundCode(?\DateTimeInterface $date = null): string
    {
        return $this->nextYearly('WSO', $date, OutboundShipment::class, 'shipment_code');
    }

    public function nextTransferCode(?\DateTimeInterface $date = null): string
    {
        return $this->nextYearly('TRF', $date, StockTransfer::class, 'transfer_code');
    }

    public function nextAdjustmentCode(?\DateTimeInterface $date = null): string
    {
        return $this->nextYearly('ADJ', $date, StockAdjustment::class, 'adjustment_code');
    }

    /**
     * @param  class-string  $model
     */
    protected function next(string $prefix, int $pad, string $model, string $column): string
    {
        $last = $this->query($model)->where($column, 'like', $prefix.'%')->orderByDesc($column)->value($column);
        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, $pad, '0', STR_PAD_LEFT);
    }

    /**
     * @param  class-string  $model
     */
    protected function nextYearly(string $code, ?\DateTimeInterface $date, string $model, string $column): string
    {
        $year = $date ? $date->format('Y') : now()->format('Y');
        $prefix = $code.'-'.$year.'-';

        $last = $this->query($model)->where($column, 'like', $prefix.'%')->orderByDesc($column)->value($column);
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
