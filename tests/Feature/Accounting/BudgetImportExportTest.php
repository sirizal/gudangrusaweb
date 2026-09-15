<?php

use App\Enums\AccountType;
use App\Filament\Accounting\Pages\ImportBudget;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\Project;
use App\Services\Accounting\BudgetExportService;
use App\Services\Accounting\BudgetImportService;
use App\Services\Accounting\BudgetService;
use App\Services\Accounting\PeriodService;
use App\Services\Accounting\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use OpenSpout\Writer\Common\Creator\WriterFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'year' => 2026,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    app(PeriodService::class)->generateForFiscalYear($this->fiscalYear);

    $this->cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create(['company_id' => $this->company->id]);
    $this->revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create(['company_id' => $this->company->id]);
    $this->expense = Account::factory()->ofType(AccountType::Expense)->withCode('6100')->create(['company_id' => $this->company->id]);
    $this->group = Account::factory()->ofType(AccountType::Asset)->withCode('1000')->group()->create(['company_id' => $this->company->id]);

    $this->costCenter = CostCenter::factory()->create(['company_id' => $this->company->id, 'code' => 'CC-01']);
    $this->department = Department::factory()->create(['company_id' => $this->company->id, 'code' => 'DEPT-01']);
    $this->project = Project::factory()->create(['company_id' => $this->company->id, 'code' => 'PRJ-01']);

    app()->instance('test_company', $this->company);
    app()->instance('test_fiscal_year', $this->fiscalYear);
});

/**
 * Capture the content of a StreamedResponse without sending headers.
 */
function captureStreamed(StreamedResponse $response): string
{
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    return is_string($content) ? $content : '';
}

function trialBalanceData(): array
{
    return [
        'fiscal_year' => app('test_fiscal_year'),
        'period' => null,
        'from' => '2026-01-01',
        'to' => '2026-12-31',
        'rows' => [
            [
                'account_code' => '1111',
                'account_name' => 'Cash',
                'opening_debit' => 0.0,
                'opening_credit' => 0.0,
                'movement_debit' => 1000000.0,
                'movement_credit' => 0.0,
                'closing_debit' => 1000000.0,
                'closing_credit' => 0.0,
            ],
            [
                'account_code' => '4100',
                'account_name' => 'Revenue',
                'opening_debit' => 0.0,
                'opening_credit' => 0.0,
                'movement_debit' => 0.0,
                'movement_credit' => 1000000.0,
                'closing_debit' => 0.0,
                'closing_credit' => 1000000.0,
            ],
        ],
        'totals' => [
            'opening_debit' => 0.0,
            'opening_credit' => 0.0,
            'movement_debit' => 1000000.0,
            'movement_credit' => 1000000.0,
            'closing_debit' => 1000000.0,
            'closing_credit' => 1000000.0,
        ],
    ];
}

it('exports a report as CSV with headers and data rows', function () {
    $response = app(ReportExportService::class)->csv('trial_balance', trialBalanceData());

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    $content = captureStreamed($response);

    expect($content)
        ->toContain('Code')
        ->toContain('Opening Dr')
        ->toContain('1111')
        ->toContain('4100')
        ->toContain('Cash')
        ->toContain('Revenue')
        ->not->toContain('Fatal error');
});

it('exports a report as XLSX with headers and data rows', function () {
    $response = app(ReportExportService::class)->xlsx('trial_balance', trialBalanceData());

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    $content = captureStreamed($response);

    $path = tempnam(sys_get_temp_dir(), 'report-').'.xlsx';
    file_put_contents($path, $content);
    $reader = ReaderFactory::createFromFile($path);
    $reader->open($path);
    $rows = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
        break;
    }
    $reader->close();
    unlink($path);

    expect($rows[0])->toContain('Code');
    expect($rows[0])->toContain('Opening Dr');
    expect(array_merge(...$rows))->toContain('1111');
    expect(array_merge(...$rows))->toContain('4100');
});

it('exports a trial balance PDF', function () {
    $response = app(ReportExportService::class)->pdf('trial_balance', trialBalanceData());

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    $content = captureStreamed($response);

    expect(substr($content, 0, 5))->toBe('%PDF-');
    expect(strlen($content))->toBeGreaterThan(10000);
});

it('exports an income statement CSV with labeled rows', function () {
    $response = app(ReportExportService::class)->csv('income_statement', [
        'fiscal_year' => app('test_fiscal_year'),
        'revenue' => 1000000.0,
        'cost_of_sales' => 200000.0,
        'gross_profit' => 800000.0,
        'operating_expenses' => 300000.0,
        'operating_profit' => 500000.0,
        'other_income' => 10000.0,
        'other_expenses' => 0.0,
        'profit_before_tax' => 510000.0,
        'income_tax' => 105000.0,
        'net_profit' => 405000.0,
    ]);

    $content = captureStreamed($response);

    expect($content)
        ->toContain('Revenue')
        ->toContain('Net Profit')
        ->toContain('405000');
});

it('exports a budget as CSV with month columns and annual total', function () {
    $budget = app(BudgetService::class)->create([
        'company_id' => $this->company->id,
        'budget_code' => 'BGT-001',
        'budget_name' => 'Test budget',
        'fiscal_year_id' => $this->fiscalYear->id,
        'lines' => [
            ['account_id' => $this->expense->id, 'month_1' => 1000000, 'month_2' => 500000],
        ],
    ]);

    $response = app(BudgetExportService::class)->csv($budget);
    $content = captureStreamed($response);

    expect($content)
        ->toContain('Account Code')
        ->toContain('6100')
        ->toContain('January')
        ->toContain('December')
        ->toContain('Annual Total')
        ->toContain('1500000');
});

it('exports a budget as XLSX', function () {
    $budget = app(BudgetService::class)->create([
        'company_id' => $this->company->id,
        'budget_code' => 'BGT-002',
        'budget_name' => 'Test budget two',
        'fiscal_year_id' => $this->fiscalYear->id,
        'lines' => [
            ['account_id' => $this->revenue->id, 'month_1' => 2500000],
        ],
    ]);

    $response = app(BudgetExportService::class)->xlsx($budget);
    $content = captureStreamed($response);

    $path = tempnam(sys_get_temp_dir(), 'budget-').'.xlsx';
    file_put_contents($path, $content);
    $reader = ReaderFactory::createFromFile($path);
    $reader->open($path);
    $rows = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
        break;
    }
    $reader->close();
    unlink($path);

    expect(array_merge(...$rows))->toContain('4100');
    expect($rows[0])->toContain('January');
    expect($rows[0])->toContain('Annual Total');
});

it('parses a valid CSV budget file into a preview without mutating data', function () {
    $path = tempnam(sys_get_temp_dir(), 'budget-import-');
    file_put_contents($path, implode("\n", [
        'Account Code,Account Name,Cost Center,Department,Project,January,February,March,April,May,June,July,August,September,October,November,December',
        '6100,Marketing Expense,CC-01,DEPT-01,PRJ-01,1000000,2000000,0,0,0,0,0,0,0,0,0,0',
    ]));

    $preview = app(BudgetImportService::class)->parse($path);
    unlink($path);

    expect($preview['valid'])->toBe(1);
    expect($preview['invalid'])->toBe(0);
    expect($preview['rows'][0]['status'])->toBe('ok');
    expect($preview['rows'][0]['account_code'])->toBe('6100');
    expect($preview['rows'][0]['annual'])->toBe(3000000.0);
    expect($preview['rows'][0]['cost_center'])->toBe($this->costCenter->name);
    expect($preview['rows'][0]['department'])->toBe($this->department->name);
    expect($preview['rows'][0]['project'])->toBe($this->project->name);
    expect(Budget::query()->count())->toBe(0);
});

it('flags unknown accounts during the import preview', function () {
    $path = tempnam(sys_get_temp_dir(), 'budget-import-');
    file_put_contents($path, implode("\n", [
        'Account Code,Account Name,January,February',
        '9999,Unknown account,1000000,0',
    ]));

    $preview = app(BudgetImportService::class)->parse($path);
    unlink($path);

    expect($preview['valid'])->toBe(0);
    expect($preview['invalid'])->toBe(1);
    expect($preview['rows'][0]['errors'][0])->toContain('Account');
});

it('flags group accounts during the import preview', function () {
    $path = tempnam(sys_get_temp_dir(), 'budget-import-');
    file_put_contents($path, implode("\n", [
        'Account Code,Account Name,January',
        '1000,Group account,1000000',
    ]));

    $preview = app(BudgetImportService::class)->parse($path);
    unlink($path);

    expect($preview['rows'][0]['status'])->toBe('error');
    expect($preview['rows'][0]['errors'][0])->toContain('postable');
});

it('flags duplicate budget lines in the same file', function () {
    $path = tempnam(sys_get_temp_dir(), 'budget-import-');
    file_put_contents($path, implode("\n", [
        'Account Code,Account Name,January',
        '6100,Marketing Expense,1000000',
        '6100,Marketing Expense,2000000',
    ]));

    $preview = app(BudgetImportService::class)->parse($path);
    unlink($path);

    expect($preview['valid'])->toBe(1);
    expect($preview['invalid'])->toBe(1);
    expect($preview['rows'][1]['errors'][0])->toContain('Duplicate');
});

it('flags non numeric month amounts during the import preview', function () {
    $path = tempnam(sys_get_temp_dir(), 'budget-import-');
    file_put_contents($path, implode("\n", [
        'Account Code,Account Name,January',
        '6100,Marketing Expense,abc',
    ]));

    $preview = app(BudgetImportService::class)->parse($path);
    unlink($path);

    expect($preview['rows'][0]['status'])->toBe('error');
    expect($preview['rows'][0]['errors'][0])->toContain('numeric');
});

it('commits valid previewed rows into an existing budget in upsert mode', function () {
    $budget = app(BudgetService::class)->create([
        'company_id' => $this->company->id,
        'budget_code' => 'BGT-001',
        'budget_name' => 'Test budget',
        'fiscal_year_id' => $this->fiscalYear->id,
        'lines' => [
            ['account_id' => $this->expense->id, 'month_1' => 100000],
        ],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'budget-import-');
    file_put_contents($path, implode("\n", [
        'Account Code,Account Name,January,February',
        '6100,Marketing Expense,1000000,500000',
        '4100,Revenue,2500000,0',
    ]));

    $preview = app(BudgetImportService::class)->parse($path);
    unlink($path);

    $imported = app(BudgetImportService::class)->commit($budget, $preview, 'upsert');

    $imported->load('lines.months');
    expect($imported->lines)->toHaveCount(2);

    $expenseLine = $imported->lines->firstWhere('account_id', $this->expense->id);
    expect((float) $expenseLine->annual_amount)->toBe(1500000.0);
    expect((float) $expenseLine->months->firstWhere('month', 1)->amount)->toBe(1000000.0);

    $revenueLine = $imported->lines->firstWhere('account_id', $this->revenue->id);
    expect((float) $revenueLine->annual_amount)->toBe(2500000.0);
});

it('replaces existing lines when committing in replace mode', function () {
    $budget = app(BudgetService::class)->create([
        'company_id' => $this->company->id,
        'budget_code' => 'BGT-001',
        'budget_name' => 'Test budget',
        'fiscal_year_id' => $this->fiscalYear->id,
        'lines' => [
            ['account_id' => $this->expense->id, 'month_1' => 100000],
            ['account_id' => $this->revenue->id, 'month_1' => 999999],
        ],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'budget-import-');
    file_put_contents($path, implode("\n", [
        'Account Code,Account Name,January',
        '6100,Marketing Expense,500000',
    ]));

    $preview = app(BudgetImportService::class)->parse($path);
    unlink($path);

    $imported = app(BudgetImportService::class)->commit($budget, $preview, 'replace');

    $imported->load('lines');
    expect($imported->lines)->toHaveCount(1);
    expect($imported->lines->first()->account_id)->toBe($this->expense->id);
});

it('refuses to import into an approved budget', function () {
    $budget = app(BudgetService::class)->create([
        'company_id' => $this->company->id,
        'budget_code' => 'BGT-001',
        'budget_name' => 'Test budget',
        'fiscal_year_id' => $this->fiscalYear->id,
        'lines' => [
            ['account_id' => $this->expense->id, 'month_1' => 100000],
        ],
    ]);
    app(BudgetService::class)->submit($budget);
    app(BudgetService::class)->approve($budget);

    $path = tempnam(sys_get_temp_dir(), 'budget-import-');
    file_put_contents($path, implode("\n", [
        'Account Code,Account Name,January',
        '6100,Marketing Expense,500000',
    ]));

    $preview = app(BudgetImportService::class)->parse($path);
    unlink($path);

    expect(fn () => app(BudgetImportService::class)->commit($budget, $preview, 'upsert'))
        ->toThrow(InvalidArgumentException::class, 'cannot be edited by import');
});

it('refuses to commit an import with no valid rows', function () {
    $budget = app(BudgetService::class)->create([
        'company_id' => $this->company->id,
        'budget_code' => 'BGT-001',
        'budget_name' => 'Test budget',
        'fiscal_year_id' => $this->fiscalYear->id,
        'lines' => [
            ['account_id' => $this->expense->id, 'month_1' => 100000],
        ],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'budget-import-');
    file_put_contents($path, implode("\n", [
        'Account Code,Account Name,January',
        '9999,Unknown account,1000000',
    ]));

    $preview = app(BudgetImportService::class)->parse($path);
    unlink($path);

    expect(fn () => app(BudgetImportService::class)->commit($budget, $preview, 'upsert'))
        ->toThrow(InvalidArgumentException::class, 'no valid rows');
});

it('parses an XLSX budget file into a preview', function () {
    $path = tempnam(sys_get_temp_dir(), 'budget-import-').'.xlsx';
    $writer = WriterFactory::createFromFile($path);
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues(['Account Code', 'Account Name', 'January', 'February']));
    $writer->addRow(Row::fromValues(['6100', 'Marketing Expense', '1000000', '500000']));
    $writer->close();

    $preview = app(BudgetImportService::class)->parse($path);
    unlink($path);

    expect($preview['valid'])->toBe(1);
    expect($preview['rows'][0]['account_code'])->toBe('6100');
    expect($preview['rows'][0]['annual'])->toBe(1500000.0);
});

it('round-trips a budget export through the importer preview', function () {
    $budget = app(BudgetService::class)->create([
        'company_id' => $this->company->id,
        'budget_code' => 'BGT-001',
        'budget_name' => 'Test budget',
        'fiscal_year_id' => $this->fiscalYear->id,
        'lines' => [
            ['account_id' => $this->expense->id, 'month_1' => 1000000, 'month_2' => 500000],
        ],
    ]);

    $csvPath = tempnam(sys_get_temp_dir(), 'budget-export-');
    $stream = fopen($csvPath, 'w');
    $budget->load('lines.months');
    $monthLabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $headers = ['Account Code', 'Account Name', 'Cost Center', 'Department', 'Project', ...$monthLabels, 'Annual Total'];
    fputcsv($stream, $headers);
    $line = $budget->lines->first();
    $months = $line->months->keyBy('month');
    fputcsv($stream, [
        $line->account->account_code,
        $line->account->account_name,
        '',
        '',
        '',
        ...array_map(fn (int $m): float => (float) ($months->get($m)?->amount ?? 0), range(1, 12)),
        (float) $line->annual_amount,
    ]);
    fclose($stream);

    $preview = app(BudgetImportService::class)->parse($csvPath);
    unlink($csvPath);

    expect($preview['valid'])->toBe(1);
    expect($preview['rows'][0]['account_code'])->toBe($line->account->account_code);
    expect($preview['rows'][0]['annual'])->toBe(1500000.0);
});

it('registers the import budget page in navigation', function () {
    expect(ImportBudget::shouldRegisterNavigation())->toBeTrue();
});

it('provides a downloadable budget import template listing every eligible account', function () {
    $response = ImportBudget::downloadTemplate();

    expect($response)->toBeInstanceOf(StreamedResponse::class);

    $content = captureStreamed($response);

    expect($content)
        ->toContain('Account Code')
        ->toContain('January')
        ->toContain('December')
        ->toContain($this->cash->account_code)
        ->toContain($this->revenue->account_code)
        ->toContain($this->expense->account_code)
        ->not->toContain($this->group->account_code);
});
