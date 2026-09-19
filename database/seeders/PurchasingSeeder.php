<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Country;
use App\Models\District;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Province;
use App\Models\PurchaseOrder;
use App\Models\SubDistrict;
use App\Models\Vendor;
use App\Models\Village;
use App\Services\Purchasing\PurchaseRequestService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Throwable;

class PurchasingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $net30 = PaymentTerm::firstOrCreate(['code' => 'NET30'], ['name' => 'Net 30', 'due_days' => 30, 'is_active' => true]);

        $country = Country::firstOrCreate(['code' => 'IDN'], ['name' => 'Indonesia', 'is_active' => true]);
        $province = Province::firstOrCreate(['country_id' => $country->id, 'code' => '31'], ['name' => 'DKI Jakarta', 'is_active' => true]);
        $district = District::firstOrCreate(['province_id' => $province->id, 'code' => '31.74'], ['name' => 'Kota Jakarta Selatan', 'is_active' => true]);
        $subDistrict = SubDistrict::firstOrCreate(['district_id' => $district->id, 'code' => '31.74.07'], ['name' => 'Kebayoran Baru', 'is_active' => true]);
        $village = Village::firstOrCreate(['sub_district_id' => $subDistrict->id, 'code' => '31.74.07.1001'], ['name' => 'Senayan', 'postal_code' => '10270', 'is_active' => true]);

        $vendor = Vendor::firstOrCreate(
            ['vendor_code' => 'VEND-0001'],
            [
                'name' => 'PT. Sumber Makmur',
                'email' => 'sales@sumbermakmur.co.id',
                'phone' => '+62 21 700 0200',
                'website' => 'https://sumbermakmur.co.id',
                'npwp' => '03.456.789.0-123.456',
                'is_pkp' => true,
                'payment_term_id' => $net30->id,
                'is_active' => true,
            ],
        );

        $vendor->addresses()->firstOrCreate(
            ['address_code' => 'OFFICE'],
            [
                'label' => 'Head Office',
                'address' => 'Jl. Senayan Raya No. 88',
                'country_id' => $country->id,
                'province_id' => $province->id,
                'district_id' => $district->id,
                'sub_district_id' => $subDistrict->id,
                'village_id' => $village->id,
                'postal_code' => $village->postal_code,
                'phone' => '+62 21 700 0200',
                'is_default' => true,
            ],
        );

        if (PurchaseOrder::where('vendor_id', $vendor->id)->exists()) {
            return;
        }

        $company = Company::firstOrCreate(['code' => 'GRU'], ['name' => 'PT. Gudang Rusa', 'is_active' => true]);
        $product = Product::query()->first();
        $expense = Account::query()->where('is_postable', true)->where('account_type', 'expense')->orderBy('account_code')->first();
        $asset = Account::query()->where('is_postable', true)->where('account_type', 'asset')->where('is_group', false)->orderBy('account_code')->first();

        if (! $product || ! $expense || ! $asset) {
            return;
        }

        try {
            $request = app(PurchaseRequestService::class)->create([
                'company_id' => $company->id,
                'vendor_id' => $vendor->id,
                'request_date' => now()->toDateString(),
                'lines' => [
                    ['purchase_type' => 'inventory', 'product_id' => $product->id, 'quantity' => 10, 'unit_price' => 250000],
                    ['purchase_type' => 'general', 'account_id' => $expense->id, 'description' => 'Office supplies', 'quantity' => 1, 'unit_price' => 1500000],
                    ['purchase_type' => 'capex', 'account_id' => $asset->id, 'description' => 'Laptop', 'quantity' => 2, 'unit_price' => 12000000],
                ],
            ]);

            $service = app(PurchaseRequestService::class);
            $service->submit($request);
            $service->approve($request);
            $service->convertToOrder($request->fresh());
        } catch (Throwable) {
            // Skip the sample order when prerequisites (accounts/products) are unavailable.
        }
    }
}
