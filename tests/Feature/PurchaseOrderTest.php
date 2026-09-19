<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Filament\Purchasing\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Purchasing\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\Account;
use App\Models\Company;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorAddress;
use App\Services\Purchasing\GoodsReceiptService;
use App\Services\Purchasing\PurchaseOrderService;
use App\Services\Purchasing\PurchaseRequestService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->company = Company::where('code', 'GRU')->firstOrFail();
    $this->term = PaymentTerm::factory()->create(['due_days' => 30]);
    $this->vendor = Vendor::factory()->create(['payment_term_id' => $this->term->id, 'is_pkp' => true]);
    $this->vendor->addresses()->create(VendorAddress::factory()->make()->toArray());
    $this->product = Product::factory()->create(['price' => 100000]);
    $this->expense = Account::where('is_postable', true)->where('account_type', 'expense')->orderBy('account_code')->firstOrFail();
    $this->asset = Account::where('is_postable', true)->where('account_type', 'asset')->where('is_group', false)->orderBy('account_code')->firstOrFail();
});

it('creates a purchase request with an auto code and converts it to a purchase order', function () {
    $service = app(PurchaseRequestService::class);

    $request = $service->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'request_date' => now()->toDateString(),
        'lines' => [
            ['purchase_type' => 'inventory', 'product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100000],
            ['purchase_type' => 'general', 'account_id' => $this->expense->id, 'quantity' => 1, 'unit_price' => 500000],
        ],
    ]);

    expect($request->request_code)->toMatch('/^PR-'.now()->year.'-\d{4}$/')
        ->and((float) $request->total)->toBe(1000000.0)
        ->and($request->status)->toBe(PurchaseRequestStatus::Draft);

    $service->submit($request);
    $service->approve($request->fresh());

    expect($request->fresh()->status)->toBe(PurchaseRequestStatus::Approved);

    $service->convertToOrder($request->fresh());

    $order = PurchaseOrder::where('purchase_request_id', $request->id)->firstOrFail();

    expect($order->po_code)->toMatch('/^PO-'.now()->year.'-\d{4}$/')
        ->and($order->status)->toBe(PurchaseOrderStatus::Open)
        ->and($order->lines)->toHaveCount(2)
        ->and((float) $order->total)->toBe(1000000.0);

    expect($request->fresh()->status)->toBe(PurchaseRequestStatus::Converted);
});

it('recomputes the order status as lines are received', function () {
    $order = app(PurchaseOrderService::class)->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'po_date' => now()->toDateString(),
        'lines' => [
            ['purchase_type' => 'general', 'account_id' => $this->expense->id, 'quantity' => 4, 'unit_price' => 100000],
        ],
    ]);

    app(PurchaseOrderService::class)->confirm($order);

    // Receive only half.
    app(GoodsReceiptService::class)->receive($order->fresh(), [
        'receipt_date' => now()->toDateString(),
        'lines' => [
            ['purchase_order_line_id' => $order->lines->first()->id, 'quantity_received' => 2],
        ],
    ]);

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived);
});

it('hydrates the order lines on the view page', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(
        Role::where('code', App\Enums\Role::SuperAdmin->value)->firstOrCreate(
            ['code' => App\Enums\Role::SuperAdmin->value],
            ['name' => App\Enums\Role::SuperAdmin->getLabel()],
        ),
    );

    \Pest\Laravel\actingAs($admin);
    filament()->setCurrentPanel('purchasing');

    $order = app(PurchaseOrderService::class)->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'po_date' => now()->toDateString(),
        'lines' => [
            ['purchase_type' => 'inventory', 'product_id' => $this->product->id, 'description' => 'Widget', 'quantity' => 2, 'unit_price' => 100000],
            ['purchase_type' => 'general', 'account_id' => $this->expense->id, 'description' => 'Supplies', 'quantity' => 1, 'unit_price' => 50000],
        ],
    ]);

    $data = Livewire::test(ViewPurchaseOrder::class, [
        'record' => $order->getRouteKey(),
    ])->get('data');

    $lines = array_values($data['lines']);

    expect($lines)->toHaveCount(2)
        ->and($lines[0]['purchase_type'])->toBe('inventory')
        ->and((string) $lines[0]['product_id'])->toBe((string) $this->product->id)
        ->and($lines[0]['description'])->toBe('Widget')
        ->and($lines[1]['purchase_type'])->toBe('general')
        ->and((string) $lines[1]['account_id'])->toBe((string) $this->expense->id);
});

it('toggles between the product and account selects based on the purchase type', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(
        Role::where('code', App\Enums\Role::SuperAdmin->value)->firstOrCreate(
            ['code' => App\Enums\Role::SuperAdmin->value],
            ['name' => App\Enums\Role::SuperAdmin->getLabel()],
        ),
    );

    \Pest\Laravel\actingAs($admin);
    filament()->setCurrentPanel('purchasing');

    $component = Livewire::test(CreatePurchaseOrder::class);

    $key = array_key_first($component->get('data.lines'));

    // Default type is inventory -> Product select is shown, account hidden.
    $inventoryLabels = implode(' | ', poFormLabels($component->html()));

    expect($inventoryLabels)->toContain('Product')
        ->and($inventoryLabels)->not->toContain('Expense account')
        ->and($inventoryLabels)->not->toContain('Asset account');

    // Switching the type to general toggles to the Expense account select.
    $generalLabels = implode(' | ', poFormLabels($component->set("data.lines.{$key}.purchase_type", 'general')->html()));

    expect($generalLabels)->toContain('Expense account')
        ->and($generalLabels)->not->toContain('Product');

    // CAPEX toggles to the Asset account select.
    $capexLabels = implode(' | ', poFormLabels($component->set("data.lines.{$key}.purchase_type", 'capex')->html()));

    expect($capexLabels)->toContain('Asset account')
        ->and($capexLabels)->not->toContain('Product');
});

function poFormLabels(string $html): array
{
    preg_match_all('/<label[^>]*>(.*?)<\/label>/s', $html, $matches);

    return array_values(array_unique(array_map(fn ($x): string => trim(strip_tags($x)), $matches[1])));
}

it('cannot confirm a non-draft order', function () {
    $order = app(PurchaseOrderService::class)->create([
        'company_id' => $this->company->id,
        'vendor_id' => $this->vendor->id,
        'po_date' => now()->toDateString(),
        'lines' => [['purchase_type' => 'general', 'account_id' => $this->expense->id, 'quantity' => 1, 'unit_price' => 100000]],
    ]);

    app(PurchaseOrderService::class)->confirm($order);

    expect(fn () => app(PurchaseOrderService::class)->confirm($order->fresh()))
        ->toThrow(InvalidArgumentException::class, 'Only draft purchase orders can be confirmed');
});

it('auto-fills the line price and description when an inventory product is selected', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(
        Role::where('code', App\Enums\Role::SuperAdmin->value)->firstOrCreate(
            ['code' => App\Enums\Role::SuperAdmin->value],
            ['name' => App\Enums\Role::SuperAdmin->getLabel()],
        ),
    );

    \Pest\Laravel\actingAs($admin);
    filament()->setCurrentPanel('purchasing');

    $product = $this->product;

    $component = Livewire::test(CreatePurchaseOrder::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'lines' => [
                ['purchase_type' => 'inventory', 'product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

    $lines = array_values($component->get('data.lines'));

    expect((float) $lines[0]['unit_price'])->toBe((float) $product->price)
        ->and($lines[0]['description'])->toBe($product->name)
        ->and((float) $lines[0]['line_total'])->toBe((float) $product->price * 3);
});
