<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\FiscalYearStatus;
use App\Enums\NormalBalance;
use App\Enums\Role;
use App\Enums\StatementType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Company;
use App\Models\CompanyBoardMember;
use App\Models\CompanyDocument;
use App\Models\CostCenter;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Department;
use App\Models\District;
use App\Models\FinancialStatementLine;
use App\Models\FiscalYear;
use App\Models\Project;
use App\Models\Province;
use App\Models\Role as RoleModel;
use App\Models\SubDistrict;
use App\Models\TaxCode;
use App\Models\User;
use App\Models\Village;
use App\Services\Accounting\BudgetService;
use App\Services\Accounting\PeriodService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['code' => 'GRU'],
            ['name' => 'PT. Gudang Rusa', 'is_active' => true],
        );

        $country = Country::firstOrCreate(
            ['code' => 'IDN'],
            ['name' => 'Indonesia', 'is_active' => true],
        );
        $province = Province::firstOrCreate(
            ['country_id' => $country->id, 'code' => '31'],
            ['name' => 'DKI Jakarta', 'is_active' => true],
        );
        $district = District::firstOrCreate(
            ['province_id' => $province->id, 'code' => '31.74'],
            ['name' => 'Kota Jakarta Selatan', 'is_active' => true],
        );
        $subDistrict = SubDistrict::firstOrCreate(
            ['district_id' => $district->id, 'code' => '31.74.07'],
            ['name' => 'Kebayoran Baru', 'is_active' => true],
        );
        $village = Village::firstOrCreate(
            ['sub_district_id' => $subDistrict->id, 'code' => '31.74.07.1001'],
            ['name' => 'Senayan', 'postal_code' => '10270', 'is_active' => true],
        );

        $company->update([
            'address' => 'Gedung Gudang Rusa, Jl. Senayan Raya No. 1',
            'country_id' => $country->id,
            'province_id' => $province->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'village_id' => $village->id,
            'postal_code' => $village->postal_code,
            'phone' => '+62 21 1234 5678',
            'email' => 'info@gudangrusa.com',
            'website' => 'https://gudangrusa.com',
            'npwp' => '01.234.567.8-901.234',
            'nib' => '1234567890123456',
            'is_pkp' => true,
            'tax_office' => 'KPP Pratama Kebayoran Baru',
            'tax_registration_date' => now()->subYears(5)->toDateString(),
        ]);

        CompanyDocument::firstOrCreate(
            ['company_id' => $company->id, 'document_number' => 'AHU-00001.AH.01.01'],
            [
                'document_type' => 'deed_of_establishment',
                'name' => 'Akta Pendirian PT. Gudang Rusa',
                'start_date' => now()->subYears(5)->toDateString(),
                'end_date' => null,
            ],
        );

        CompanyBoardMember::firstOrCreate(
            ['company_id' => $company->id, 'nik' => '3174010101900001'],
            [
                'name' => 'Budi Santoso',
                'position' => 'president_director',
                'start_date' => now()->subYears(5)->toDateString(),
                'end_date' => null,
                'is_active' => true,
            ],
        );

        Currency::firstOrCreate(
            ['code' => 'IDR'],
            ['name' => 'Rupiah', 'symbol' => 'Rp', 'decimal_places' => 2, 'is_base_currency' => true, 'is_active' => true],
        );

        foreach (Role::cases() as $role) {
            RoleModel::firstOrCreate(
                ['code' => $role->value],
                ['name' => $role->getLabel()],
            );
        }

        $fiscalYear = FiscalYear::firstOrCreate(
            ['company_id' => $company->id, 'year' => now()->year],
            [
                'name' => 'FY '.now()->year,
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'status' => FiscalYearStatus::Open,
                'is_current' => true,
            ],
        );

        app(PeriodService::class)->generateForFiscalYear($fiscalYear);

        $this->seedChartOfAccounts($company);
        $this->seedDimensions($company);
        $this->seedStatementLines();
        $this->seedTaxCodes();
        $this->seedBudgets($company, $fiscalYear);

        if ($admin = User::query()->where('email', 'test@example.com')->first()) {
            $admin->roles()->syncWithoutDetaching(
                RoleModel::query()->whereIn('code', [Role::SuperAdmin->value, Role::FinanceManager->value, Role::Accountant->value])->pluck('id'),
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     */
    private function createAccountTree(Company $company, array $tree, ?Account $parent = null): void
    {
        foreach ($tree as $node) {
            $isGroup = (bool) ($node['is_group'] ?? false);

            $account = Account::firstOrCreate(
                ['company_id' => $company->id, 'account_code' => $node['code']],
                [
                    'parent_id' => $parent?->id,
                    'account_name' => $node['name'],
                    'account_name_en' => $node['name_en'] ?? null,
                    'account_type' => $node['type'],
                    'normal_balance' => $node['balance'],
                    'level' => ($parent?->level ?? 0) + 1,
                    'is_group' => $isGroup,
                    'is_postable' => ! $isGroup,
                    'is_active' => true,
                    'cash_flow_activity' => $node['cash_flow_activity'] ?? null,
                    'description' => $node['description'] ?? null,
                ],
            );

            if (! empty($node['children'])) {
                $this->createAccountTree($company, $node['children'], $account);
            }
        }
    }

    private function seedChartOfAccounts(Company $company): void
    {
        $this->createAccountTree($company, [
            [
                'code' => '1000', 'name' => 'ASSET', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit, 'is_group' => true,
                'children' => [
                    [
                        'code' => '1100', 'name' => 'Current Assets', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit, 'is_group' => true,
                        'children' => [
                            [
                                'code' => '1110', 'name' => 'Cash', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit, 'is_group' => true, 'cash_flow_activity' => 'operating',
                                'children' => [
                                    ['code' => '1111', 'name' => 'Petty Cash', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit, 'cash_flow_activity' => 'operating'],
                                    ['code' => '1112', 'name' => 'Bank Account', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit, 'cash_flow_activity' => 'operating'],
                                ],
                            ],
                            ['code' => '1120', 'name' => 'Accounts Receivable', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit],
                            ['code' => '1130', 'name' => 'Inventory', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit],
                            ['code' => '1140', 'name' => 'Prepaid Expenses', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit],
                        ],
                    ],
                    [
                        'code' => '1200', 'name' => 'Non-Current Assets', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit, 'is_group' => true,
                        'children' => [
                            [
                                'code' => '1210', 'name' => 'Fixed Assets', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit, 'is_group' => true,
                                'children' => [
                                    ['code' => '1211', 'name' => 'Land', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit],
                                    ['code' => '1212', 'name' => 'Building', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit],
                                    ['code' => '1213', 'name' => 'Machinery', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit],
                                    ['code' => '1214', 'name' => 'Vehicles', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit],
                                    ['code' => '1215', 'name' => 'Office Equipment', 'type' => AccountType::Asset, 'balance' => NormalBalance::Debit],
                                ],
                            ],
                            ['code' => '1220', 'name' => 'Accumulated Depreciation', 'type' => AccountType::Asset, 'balance' => NormalBalance::Credit],
                        ],
                    ],
                ],
            ],
            [
                'code' => '2000', 'name' => 'LIABILITIES', 'type' => AccountType::Liability, 'balance' => NormalBalance::Credit, 'is_group' => true,
                'children' => [
                    [
                        'code' => '2100', 'name' => 'Current Liabilities', 'type' => AccountType::Liability, 'balance' => NormalBalance::Credit, 'is_group' => true,
                        'children' => [
                            ['code' => '2110', 'name' => 'Accounts Payable', 'type' => AccountType::Liability, 'balance' => NormalBalance::Credit],
                            ['code' => '2120', 'name' => 'Accrued Expenses', 'type' => AccountType::Liability, 'balance' => NormalBalance::Credit],
                            ['code' => '2130', 'name' => 'Tax Payable', 'type' => AccountType::Liability, 'balance' => NormalBalance::Credit],
                            ['code' => '2140', 'name' => 'Employee Payable', 'type' => AccountType::Liability, 'balance' => NormalBalance::Credit],
                            ['code' => '2150', 'name' => 'Goods Received Not Invoiced', 'type' => AccountType::Liability, 'balance' => NormalBalance::Credit],
                        ],
                    ],
                    [
                        'code' => '2200', 'name' => 'Non-Current Liabilities', 'type' => AccountType::Liability, 'balance' => NormalBalance::Credit, 'is_group' => true,
                        'children' => [
                            ['code' => '2210', 'name' => 'Long Term Loan', 'type' => AccountType::Liability, 'balance' => NormalBalance::Credit],
                            ['code' => '2220', 'name' => 'Lease Liability', 'type' => AccountType::Liability, 'balance' => NormalBalance::Credit],
                        ],
                    ],
                ],
            ],
            [
                'code' => '3000', 'name' => 'EQUITY', 'type' => AccountType::Equity, 'balance' => NormalBalance::Credit, 'is_group' => true,
                'children' => [
                    ['code' => '3100', 'name' => 'Share Capital', 'type' => AccountType::Equity, 'balance' => NormalBalance::Credit],
                    ['code' => '3200', 'name' => 'Retained Earnings', 'type' => AccountType::Equity, 'balance' => NormalBalance::Credit],
                    ['code' => '3300', 'name' => 'Current Year Profit/Loss', 'type' => AccountType::Equity, 'balance' => NormalBalance::Credit],
                ],
            ],
            [
                'code' => '4000', 'name' => 'REVENUE', 'type' => AccountType::Revenue, 'balance' => NormalBalance::Credit, 'is_group' => true,
                'children' => [
                    ['code' => '4100', 'name' => 'Sales Revenue', 'type' => AccountType::Revenue, 'balance' => NormalBalance::Credit],
                    ['code' => '4200', 'name' => 'Service Revenue', 'type' => AccountType::Revenue, 'balance' => NormalBalance::Credit],
                    ['code' => '4300', 'name' => 'Other Operating Revenue', 'type' => AccountType::Revenue, 'balance' => NormalBalance::Credit],
                ],
            ],
            [
                'code' => '5000', 'name' => 'COST OF SALES', 'type' => AccountType::CostOfSales, 'balance' => NormalBalance::Debit, 'is_group' => true,
                'children' => [
                    ['code' => '5100', 'name' => 'Cost of Goods Sold', 'type' => AccountType::CostOfSales, 'balance' => NormalBalance::Debit],
                    ['code' => '5200', 'name' => 'Direct Cost', 'type' => AccountType::CostOfSales, 'balance' => NormalBalance::Debit],
                ],
            ],
            [
                'code' => '6000', 'name' => 'OPERATING EXPENSE', 'type' => AccountType::Expense, 'balance' => NormalBalance::Debit, 'is_group' => true,
                'children' => [
                    ['code' => '6100', 'name' => 'Employee Expense', 'type' => AccountType::Expense, 'balance' => NormalBalance::Debit],
                    ['code' => '6200', 'name' => 'Rent Expense', 'type' => AccountType::Expense, 'balance' => NormalBalance::Debit],
                    ['code' => '6300', 'name' => 'Utilities', 'type' => AccountType::Expense, 'balance' => NormalBalance::Debit],
                    ['code' => '6400', 'name' => 'Transportation', 'type' => AccountType::Expense, 'balance' => NormalBalance::Debit],
                    ['code' => '6500', 'name' => 'Office Expense', 'type' => AccountType::Expense, 'balance' => NormalBalance::Debit],
                    ['code' => '6600', 'name' => 'Depreciation Expense', 'type' => AccountType::Expense, 'balance' => NormalBalance::Debit],
                    ['code' => '6700', 'name' => 'Professional Fees', 'type' => AccountType::Expense, 'balance' => NormalBalance::Debit],
                    ['code' => '6800', 'name' => 'IT Expense', 'type' => AccountType::Expense, 'balance' => NormalBalance::Debit],
                    ['code' => '6900', 'name' => 'Other Operating Expense', 'type' => AccountType::Expense, 'balance' => NormalBalance::Debit],
                ],
            ],
            [
                'code' => '7000', 'name' => 'OTHER INCOME', 'type' => AccountType::OtherIncome, 'balance' => NormalBalance::Credit, 'is_group' => true,
                'children' => [
                    ['code' => '7100', 'name' => 'Interest Income', 'type' => AccountType::OtherIncome, 'balance' => NormalBalance::Credit],
                    ['code' => '7200', 'name' => 'Gain on Asset Disposal', 'type' => AccountType::OtherIncome, 'balance' => NormalBalance::Credit],
                ],
            ],
            [
                'code' => '8000', 'name' => 'OTHER EXPENSE', 'type' => AccountType::OtherExpense, 'balance' => NormalBalance::Debit, 'is_group' => true,
                'children' => [
                    ['code' => '8100', 'name' => 'Interest Expense', 'type' => AccountType::OtherExpense, 'balance' => NormalBalance::Debit],
                    ['code' => '8200', 'name' => 'Loss on Asset Disposal', 'type' => AccountType::OtherExpense, 'balance' => NormalBalance::Debit],
                    ['code' => '8300', 'name' => 'Tax Expense', 'type' => AccountType::OtherExpense, 'balance' => NormalBalance::Debit],
                ],
            ],
        ]);
    }

    private function seedDimensions(Company $company): void
    {
        $headquarters = CostCenter::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'CC-HQ'],
            ['name' => 'Headquarters', 'is_active' => true],
        );

        foreach (['ADM' => 'Administration', 'FIN' => 'Finance', 'IT' => 'IT', 'WRH' => 'Warehouse', 'MKT' => 'Marketing'] as $code => $name) {
            CostCenter::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'CC-HQ-'.$code],
                ['name' => $name, 'parent_id' => $headquarters->id, 'is_active' => true],
            );
        }

        $headDept = Department::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DEPT-HQ'],
            ['name' => 'Headquarters', 'is_active' => true],
        );

        foreach (['ADM' => 'Administration', 'FIN' => 'Finance', 'IT' => 'IT', 'OPS' => 'Operations', 'MKT' => 'Marketing'] as $code => $name) {
            Department::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'DEPT-HQ-'.$code],
                ['name' => $name, 'parent_id' => $headDept->id, 'is_active' => true],
            );
        }

        Project::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'PRJ-GENERAL'],
            ['name' => 'General Operations', 'is_active' => true],
        );
    }

    private function seedStatementLines(): void
    {
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-CASH', 'Cash and Cash Equivalents', 10, ['1110'], includeDescendants: true);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-AR', 'Accounts Receivable', 20, ['1120']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-INV', 'Inventory', 30, ['1130']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-OCA', 'Other Current Assets', 40, ['1140']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-FA', 'Fixed Assets', 50, ['1210'], includeDescendants: true);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-AD', 'Accumulated Depreciation', 60, ['1220']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-AP', 'Accounts Payable', 70, ['2110']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-GRNI', 'Goods Received Not Invoiced', 71, ['2150']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-ACR', 'Accrued Liabilities', 80, ['2120']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-TAX', 'Tax Payables', 90, ['2130']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-LOAN', 'Loans', 100, ['2210', '2220']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-OLIAB', 'Other Liabilities', 110, ['2140']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-SC', 'Share Capital', 120, ['3100']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-RE', 'Retained Earnings', 130, ['3200']);
        $this->createStatementLine(StatementType::BalanceSheet, 'BS-CYPL', 'Current Year Profit/Loss', 140, ['3300']);

        $this->createStatementLine(StatementType::IncomeStatement, 'IS-REV', 'Revenue', 10, ['4100'], includeDescendants: true);
        $this->createStatementLine(StatementType::IncomeStatement, 'IS-COS', 'Cost of Sales', 20, ['5100'], includeDescendants: true);
        $this->createStatementLine(StatementType::IncomeStatement, 'IS-GP', 'Gross Profit', 30);
        $this->createStatementLine(StatementType::IncomeStatement, 'IS-OPEX', 'Operating Expenses', 40, ['6100', '6200', '6300', '6400', '6500', '6600', '6700', '6800', '6900'], includeDescendants: true);
        $this->createStatementLine(StatementType::IncomeStatement, 'IS-OP', 'Operating Profit', 50);
        $this->createStatementLine(StatementType::IncomeStatement, 'IS-OI', 'Other Income', 60, ['7100', '7200']);
        $this->createStatementLine(StatementType::IncomeStatement, 'IS-OE', 'Other Expenses', 70, ['8100', '8200']);
        $this->createStatementLine(StatementType::IncomeStatement, 'IS-PBT', 'Profit Before Tax', 80);
        $this->createStatementLine(StatementType::IncomeStatement, 'IS-TAX', 'Income Tax', 90, ['8300']);
        $this->createStatementLine(StatementType::IncomeStatement, 'IS-NP', 'Net Profit', 100);
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function createStatementLine(StatementType $type, string $code, string $name, int $sequence, array $codes = [], bool $includeDescendants = false): void
    {
        $line = FinancialStatementLine::firstOrCreate(
            ['statement_type' => $type, 'code' => $code],
            ['name' => $name, 'sequence' => $sequence, 'is_active' => true],
        );

        $accounts = Account::query()
            ->whereIn('account_code', $codes)
            ->get();

        $line->accounts()->syncWithPivotValues(
            $accounts->pluck('id')->all(),
            ['include_descendants' => $includeDescendants],
        );
    }

    private function seedTaxCodes(): void
    {
        $taxPayable = Account::query()->where('account_code', '2130')->first();

        TaxCode::firstOrCreate(
            ['code' => 'PPN-OUT'],
            ['name' => 'PPN Keluaran', 'tax_type' => 'PPN', 'rate' => 11, 'payable_account_id' => $taxPayable?->id, 'receivable_account_id' => $taxPayable?->id, 'is_active' => true],
        );

        TaxCode::firstOrCreate(
            ['code' => 'PPN-IN'],
            ['name' => 'PPN Masukan', 'tax_type' => 'PPN', 'rate' => 11, 'account_id' => $taxPayable?->id, 'receivable_account_id' => $taxPayable?->id, 'is_active' => true],
        );

        TaxCode::firstOrCreate(
            ['code' => 'PPH23'],
            ['name' => 'PPh Pasal 23', 'tax_type' => 'PPh 23', 'rate' => 2, 'payable_account_id' => $taxPayable?->id, 'is_active' => true],
        );

        TaxCode::firstOrCreate(
            ['code' => 'PPH4'],
            ['name' => 'PPh Final 4(2)', 'tax_type' => 'PPh 4(2)', 'rate' => 0.5, 'payable_account_id' => $taxPayable?->id, 'is_active' => true],
        );

        TaxCode::firstOrCreate(
            ['code' => 'NO-TAX'],
            ['name' => 'No Tax', 'tax_type' => 'PPN', 'rate' => 0, 'is_active' => true],
        );
    }

    private function seedBudgets(Company $company, FiscalYear $fiscalYear): void
    {
        if (Budget::query()->where('budget_code', 'BGT-'.now()->year.'-001')->exists()) {
            return;
        }

        $accountFor = fn (string $code): int => Account::query()
            ->where('company_id', $company->id)
            ->where('account_code', $code)
            ->firstOrFail()
            ->id;

        app(BudgetService::class)->create([
            'company_id' => $company->id,
            'budget_code' => 'BGT-'.now()->year.'-001',
            'budget_name' => 'Annual Budget '.now()->year,
            'fiscal_year_id' => $fiscalYear->id,
            'description' => 'Sample operational budget generated by the seeder.',
            'lines' => [
                [
                    'account_id' => $accountFor('4100'),
                    'description' => 'Sales revenue',
                    'month_1' => 150000000,
                    'month_2' => 150000000,
                    'month_3' => 150000000,
                    'month_4' => 160000000,
                    'month_5' => 160000000,
                    'month_6' => 160000000,
                    'month_7' => 160000000,
                    'month_8' => 160000000,
                    'month_9' => 160000000,
                    'month_10' => 165000000,
                    'month_11' => 165000000,
                    'month_12' => 165000000,
                ],
                [
                    'account_id' => $accountFor('6100'),
                    'description' => 'Employee expense',
                    'month_1' => 80000000,
                    'month_2' => 80000000,
                    'month_3' => 80000000,
                    'month_4' => 80000000,
                    'month_5' => 80000000,
                    'month_6' => 80000000,
                    'month_7' => 80000000,
                    'month_8' => 80000000,
                    'month_9' => 80000000,
                    'month_10' => 80000000,
                    'month_11' => 80000000,
                    'month_12' => 80000000,
                ],
                [
                    'account_id' => $accountFor('6200'),
                    'description' => 'Rent expense',
                    'month_1' => 25000000,
                    'month_2' => 25000000,
                    'month_3' => 25000000,
                    'month_4' => 25000000,
                    'month_5' => 25000000,
                    'month_6' => 25000000,
                    'month_7' => 25000000,
                    'month_8' => 25000000,
                    'month_9' => 25000000,
                    'month_10' => 25000000,
                    'month_11' => 25000000,
                    'month_12' => 25000000,
                ],
            ],
        ]);
    }
}
