<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesOrder;

class SalesNumberGenerator
{
    /**
     * Generate the next customer code in the format CUST-0001.
     */
    public function nextCustomerCode(): string
    {
        $prefix = 'CUST-';

        $last = Customer::withTrashed()
            ->where('customer_code', 'like', $prefix.'%')
            ->orderByDesc('customer_code')
            ->value('customer_code');

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate the next sales order code in the format SO-YYYY-0001.
     */
    public function nextOrderCode(?\DateTimeInterface $date = null): string
    {
        $year = $date ? $date->format('Y') : now()->format('Y');
        $prefix = 'SO-'.$year.'-';

        $last = SalesOrder::withTrashed()
            ->where('order_code', 'like', $prefix.'%')
            ->orderByDesc('order_code')
            ->value('order_code');

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate the next invoice code in the format INV-YYYY-0001.
     */
    public function nextInvoiceCode(?\DateTimeInterface $date = null): string
    {
        $year = $date ? $date->format('Y') : now()->format('Y');
        $prefix = 'INV-'.$year.'-';

        $last = Invoice::withTrashed()
            ->where('invoice_code', 'like', $prefix.'%')
            ->orderByDesc('invoice_code')
            ->value('invoice_code');

        $sequence = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
