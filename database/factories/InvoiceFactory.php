<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentTerm;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $invoiceDate = fake()->date();

        return [
            'invoice_code' => 'INV-'.substr($invoiceDate, 0, 4).'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'sales_order_id' => SalesOrder::factory(),
            'customer_id' => Customer::factory(),
            'invoice_date' => $invoiceDate,
            'due_date' => Carbon::parse($invoiceDate)->addDays(30)->toDateString(),
            'payment_term_id' => PaymentTerm::factory(),
            'status' => 'issued',
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'paid_amount' => 0,
        ];
    }
}
