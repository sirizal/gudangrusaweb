<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\District;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Province;
use App\Models\SalesOrder;
use App\Models\SubDistrict;
use App\Models\Village;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SalesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $net30 = PaymentTerm::firstOrCreate(
            ['code' => 'NET30'],
            ['name' => 'Net 30', 'due_days' => 30, 'is_active' => true],
        );
        PaymentTerm::firstOrCreate(
            ['code' => 'NET14'],
            ['name' => 'Net 14', 'due_days' => 14, 'is_active' => true],
        );
        PaymentTerm::firstOrCreate(
            ['code' => 'COD'],
            ['name' => 'Cash on Delivery', 'due_days' => 0, 'is_active' => true],
        );

        $country = Country::firstOrCreate(['code' => 'IDN'], ['name' => 'Indonesia', 'is_active' => true]);
        $province = Province::firstOrCreate(['country_id' => $country->id, 'code' => '31'], ['name' => 'DKI Jakarta', 'is_active' => true]);
        $district = District::firstOrCreate(['province_id' => $province->id, 'code' => '31.74'], ['name' => 'Kota Jakarta Selatan', 'is_active' => true]);
        $subDistrict = SubDistrict::firstOrCreate(['district_id' => $district->id, 'code' => '31.74.07'], ['name' => 'Kebayoran Baru', 'is_active' => true]);
        $village = Village::firstOrCreate(['sub_district_id' => $subDistrict->id, 'code' => '31.74.07.1001'], ['name' => 'Senayan', 'postal_code' => '10270', 'is_active' => true]);

        $customer = Customer::firstOrCreate(
            ['customer_code' => 'CUST-0001'],
            [
                'name' => 'PT. Mitra Jaya',
                'email' => 'finance@mitrajaya.co.id',
                'phone' => '+62 21 555 0100',
                'website' => 'https://mitrajaya.co.id',
                'npwp' => '02.345.678.9-012.345',
                'is_pkp' => true,
                'credit_limit' => 25000000,
                'payment_term_id' => $net30->id,
                'is_active' => true,
            ],
        );

        CustomerAddress::firstOrCreate(
            ['customer_id' => $customer->id, 'address_code' => 'OFFICE'],
            [
                'label' => 'Head Office',
                'address' => 'Jl. Senayan Raya No. 12',
                'country_id' => $country->id,
                'province_id' => $province->id,
                'district_id' => $district->id,
                'sub_district_id' => $subDistrict->id,
                'village_id' => $village->id,
                'postal_code' => $village->postal_code,
                'phone' => '+62 21 555 0100',
                'is_billing' => true,
                'is_shipping' => true,
                'is_default' => true,
            ],
        );

        $company = Company::firstOrCreate(['code' => 'GRU'], ['name' => 'PT. Gudang Rusa', 'is_active' => true]);
        $product = Product::query()->first();

        $order = SalesOrder::firstOrCreate(
            ['order_code' => 'SO-'.now()->year.'-0001'],
            [
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'billing_address_id' => $customer->addresses()->first()?->id,
                'shipping_address_id' => $customer->addresses()->first()?->id,
                'order_date' => now()->toDateString(),
                'payment_term_id' => $net30->id,
                'status' => 'raised',
                'subtotal' => $product ? (float) $product->price : 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => $product ? (float) $product->price : 0,
            ],
        );

        if ($order->lines()->doesntExist()) {
            $order->lines()->create([
                'product_id' => $product?->id,
                'description' => $product?->name ?? 'Sample line item',
                'quantity' => 1,
                'unit_price' => $product ? (float) $product->price : 0,
                'line_total' => $product ? (float) $product->price : 0,
            ]);
        }
    }
}
