<?php

use App\Enums\Role;
use App\Filament\Accounting\Resources\Companies\Pages\CreateCompany;
use App\Filament\Accounting\Resources\Companies\Pages\EditCompany;
use App\Filament\Accounting\Resources\Companies\RelationManagers\CompanyBoardMembersRelationManager;
use App\Filament\Accounting\Resources\Companies\RelationManagers\CompanyDocumentsRelationManager;
use App\Models\Company;
use App\Models\CompanyBoardMember;
use App\Models\CompanyDocument;
use App\Models\Country;
use App\Models\Province;
use App\Models\Role as RoleModel;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(
        RoleModel::where('code', Role::SuperAdmin->value)->firstOrCreate(
            ['code' => Role::SuperAdmin->value],
            ['name' => Role::SuperAdmin->getLabel()],
        ),
    );

    actingAs($this->admin);

    filament()->setCurrentPanel('accounting');

    $this->company = Company::where('code', 'GRU')->firstOrFail();
});

it('stores the seeded company with geography and tax profile', function () {
    expect($this->company->npwp)->toBe('01.234.567.8-901.234')
        ->and($this->company->is_pkp)->toBeTrue()
        ->and($this->company->village->subDistrict->district->province->country->code)->toBe('IDN')
        ->and($this->company->postal_code)->not->toBeNull()
        ->and($this->company->documents()->count())->toBe(1)
        ->and($this->company->boardMembers()->count())->toBe(1);
});

it('creates a company with legal address and tax information', function () {
    $village = Village::query()->firstOrFail();

    Livewire::test(CreateCompany::class)
        ->fillForm([
            'code' => 'ABC',
            'name' => 'PT. Contoh Sejahtera',
            'is_active' => true,
            'country_id' => $village->subDistrict->district->province->country->id,
            'province_id' => $village->subDistrict->district->province->id,
            'district_id' => $village->subDistrict->district->id,
            'sub_district_id' => $village->subDistrict->id,
            'village_id' => $village->id,
            'address' => 'Jl. Contoh No. 10',
            'postal_code' => $village->postal_code,
            'npwp' => '02.345.678.9-012.345',
            'nib' => '9999999999999999',
            'is_pkp' => true,
            'tax_office' => 'KPP Pratama Gambir',
            'tax_registration_date' => '2020-01-01',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $company = Company::where('code', 'ABC')->firstOrFail();

    expect($company->village->is($village))->toBeTrue()
        ->and($company->npwp)->toBe('02.345.678.9-012.345')
        ->and($company->is_pkp)->toBeTrue();
});

it('rejects an invalid NPWP format', function () {
    Livewire::test(CreateCompany::class)
        ->fillForm([
            'code' => 'ABC',
            'name' => 'PT. Contoh Sejahtera',
            'npwp' => '12345',
        ])
        ->call('create')
        ->assertHasFormErrors(['npwp']);
});

it('filters the dependent geography selects by the selected parent', function () {
    $province = Province::query()->where('code', '31')->firstOrFail();
    $otherCountry = Country::factory()->create();
    $other = Province::factory()->create(['country_id' => $otherCountry->id, 'code' => '99']);

    Livewire::test(CreateCompany::class)
        ->fillForm(['country_id' => $province->country_id])
        ->assertSee($province->name)
        ->assertDontSee($other->name);
});

it('stores a company document with its file on the public disk', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->create('deed.pdf', 20);

    Livewire::test(CompanyDocumentsRelationManager::class, [
        'ownerRecord' => $this->company,
        'pageClass' => EditCompany::class,
    ])
        ->callTableAction('create', data: [
            'document_type' => 'deed_of_establishment',
            'document_number' => 'AHU-00002',
            'name' => 'Akta Pendirian',
            'start_date' => '2020-01-01',
            'end_date' => null,
            'file_path' => $file,
        ])
        ->assertHasNoTableActionErrors();

    $document = CompanyDocument::where('document_number', 'AHU-00002')->firstOrFail();

    expect($document->company_id)->toBe($this->company->id)
        ->and($document->file_path)->not->toBeNull();

    Storage::disk('public')->assertExists($document->file_path);
});

it('validates board member NIK and dates', function () {
    Livewire::test(CompanyBoardMembersRelationManager::class, [
        'ownerRecord' => $this->company,
        'pageClass' => EditCompany::class,
    ])
        ->callTableAction('create', data: [
            'name' => 'Siti Rahayu',
            'position' => 'director',
            'nik' => '123',
            'start_date' => '2021-05-01',
            'end_date' => '2020-01-01',
        ])
        ->assertHasTableActionErrors(['nik', 'end_date']);

    expect(CompanyBoardMember::where('name', 'Siti Rahayu')->count())->toBe(0);
});

it('allows board members to be created', function () {
    Livewire::test(CompanyBoardMembersRelationManager::class, [
        'ownerRecord' => $this->company,
        'pageClass' => EditCompany::class,
    ])
        ->callTableAction('create', data: [
            'name' => 'Siti Rahayu',
            'position' => 'finance_director',
            'nik' => '3174015105900002',
            'start_date' => '2021-05-01',
            'end_date' => null,
            'is_active' => true,
        ])
        ->assertHasNoTableActionErrors();

    expect(CompanyBoardMember::where('nik', '3174015105900002')->firstOrFail()->position->value)->toBe('finance_director');
});

it('restricts document and board member management to super admins', function () {
    $viewer = User::factory()->create();
    $viewer->roles()->attach(
        RoleModel::where('code', Role::Viewer->value)->firstOrCreate(
            ['code' => Role::Viewer->value],
            ['name' => Role::Viewer->getLabel()],
        ),
    );

    actingAs($viewer);

    expect($viewer->can('create', CompanyDocument::class))->toBeFalse()
        ->and($viewer->can('create', CompanyBoardMember::class))->toBeFalse()
        ->and($viewer->can('view', $this->company))->toBeTrue();
});
