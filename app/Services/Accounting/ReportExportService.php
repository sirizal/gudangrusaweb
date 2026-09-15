<?php

namespace App\Services\Accounting;

use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Column definitions per report type: [accessor path, header label, isNumeric].
     *
     * @var array<string, array<int, array{0: string, 1: string, 2: bool}>>
     */
    protected array $columns = [
        'trial_balance' => [
            ['account_code', 'Code', false],
            ['account_name', 'Account', false],
            ['opening_debit', 'Opening Dr', true],
            ['opening_credit', 'Opening Cr', true],
            ['movement_debit', 'Movement Dr', true],
            ['movement_credit', 'Movement Cr', true],
            ['closing_debit', 'Closing Dr', true],
            ['closing_credit', 'Closing Cr', true],
        ],
        'general_ledger' => [
            ['date', 'Date', false],
            ['journal_number', 'Journal No', false],
            ['description', 'Description', false],
            ['debit', 'Debit', true],
            ['credit', 'Credit', true],
            ['balance', 'Balance', true],
        ],
        'income_statement' => [
            ['label', 'Line', false],
            ['amount', 'Amount', true],
        ],
        'balance_sheet' => [
            ['label', 'Line', false],
            ['amount', 'Amount', true],
        ],
        'cash_flow' => [
            ['label', 'Line', false],
            ['amount', 'Amount', true],
        ],
        'budget_vs_actual' => [
            ['account_code', 'Account Code', false],
            ['account_name', 'Account', false],
            ['cost_center_name', 'Cost Center', false],
            ['department_name', 'Department', false],
            ['project_name', 'Project', false],
            ['budget', 'Budget', true],
            ['actual', 'Actual', true],
            ['variance', 'Variance', true],
            ['variance_pct', 'Variance %', true],
        ],
    ];

    /**
     * Human-readable titles per report type.
     *
     * @var array<string, string>
     */
    protected array $titles = [
        'trial_balance' => 'Trial Balance',
        'general_ledger' => 'General Ledger',
        'income_statement' => 'Income Statement',
        'balance_sheet' => 'Balance Sheet',
        'cash_flow' => 'Cash Flow Statement',
        'budget_vs_actual' => 'Budget vs Actual',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function csv(string $type, array $data): StreamedResponse
    {
        [$headers, $rows, $fileName] = $this->table($type, $data);

        return response()->streamDownload(function () use ($headers, $rows): void {
            $stream = fopen('php://output', 'w');

            fputcsv($stream, array_map(fn (array $c): string => $c[1], $headers), ',', '"', '\\');

            foreach ($rows as $row) {
                fputcsv($stream, array_map(fn (int $i) => $row[$i], array_keys($headers)), ',', '"', '\\');
            }

            fclose($stream);
        }, $fileName.'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function xlsx(string $type, array $data): StreamedResponse
    {
        [$headers, $rows, $fileName] = $this->table($type, $data);

        $path = tempnam(sys_get_temp_dir(), 'report-').'.xlsx';
        $writer = WriterFactory::createFromFile($path);
        $writer->openToFile($path);

        $writer->addRow(Row::fromValues(array_map(fn (array $c): string => $c[1], $headers)));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_map(fn (int $i) => $row[$i], array_keys($headers))));
        }

        $writer->close();

        return response()->streamDownload(function () use ($path): void {
            echo file_get_contents($path);
            @unlink($path);
        }, $fileName.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function pdf(string $type, array $data): StreamedResponse
    {
        [$columns, $rows, $fileName, $title, $subtitle] = $this->table($type, $data);

        $headers = array_map(fn (array $c): string => $c[1], $columns);

        $pdfRows = array_map(function (array $row) use ($columns): array {
            $cells = [];

            foreach ($columns as $i => $column) {
                $cells[] = [
                    'value' => $column[2]
                        ? number_format((float) ($row[$i] ?? 0), 2, ',', '.')
                        : (string) ($row[$i] ?? ''),
                    'numeric' => $column[2],
                ];
            }

            return ['cells' => $cells];
        }, $rows);

        $pdf = Pdf::loadView('pdf.accounting.report', [
            'title' => $title,
            'subtitle' => $subtitle,
            'headers' => $headers,
            'rows' => $pdfRows,
            'note' => null,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $fileName.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Build the headers, data rows, and file name for a report type.
     *
     * @param  array<string, mixed>  $data
     * @return array{
     *     0: array<int, array{0: string, 1: string, 2: bool}>,
     *     1: array<int, array<int, string|int|float|null>>,
     *     2: string,
     *     3: string,
     *     4: string,
     * }
     */
    protected function table(string $type, array $data): array
    {
        $rows = array_map(
            fn (array $row): array => array_map(fn (array $c) => $this->extract($row, $c[0]), $this->columns[$type]),
            $this->resolveRows($type, $data),
        );

        $title = $this->titles[$type];
        $subtitle = $this->subtitle($data);

        return [$this->columns[$type], $rows, $this->fileName($type, $data), $title, $subtitle];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function extract(array $row, string $path): string|int|float|null
    {
        return data_get($row, $path);
    }

    /**
     * Resident rows for a report type. Statement reports (income statement,
     * balance sheet, cash flow) are rendered as line/amount rows.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function resolveRows(string $type, array $data): array
    {
        if ($type === 'general_ledger') {
            $rows = $data['rows'] ?? [];

            if (isset($data['opening_balance'])) {
                array_unshift($rows, [
                    'date' => $data['from'],
                    'journal_number' => '',
                    'description' => 'Opening balance',
                    'debit' => 0,
                    'credit' => 0,
                    'balance' => $data['opening_balance'],
                ]);
            }

            if (isset($data['closing_balance'])) {
                $rows[] = [
                    'date' => $data['to'],
                    'journal_number' => '',
                    'description' => 'Closing balance',
                    'debit' => 0,
                    'credit' => 0,
                    'balance' => $data['closing_balance'],
                ];
            }

            return $rows;
        }

        if (in_array($type, ['income_statement', 'balance_sheet', 'cash_flow'], true)) {
            return $this->statementRows($type, $data);
        }

        return $data['rows'] ?? [];
    }

    /**
     * Convert a statement report array into labeled rows.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function statementRows(string $type, array $data): array
    {
        $definitions = [
            'income_statement' => [
                'Revenue' => 'revenue',
                'Cost of Sales' => 'cost_of_sales',
                'Gross Profit' => 'gross_profit',
                'Operating Expenses' => 'operating_expenses',
                'Operating Profit' => 'operating_profit',
                'Other Income' => 'other_income',
                'Other Expenses' => 'other_expenses',
                'Profit Before Tax' => 'profit_before_tax',
                'Income Tax' => 'income_tax',
                'Net Profit' => 'net_profit',
            ],
            'balance_sheet' => [
                'Cash' => 'cash',
                'Accounts Receivable' => 'accounts_receivable',
                'Current Assets' => 'current_assets',
                'Non-current Assets' => 'non_current_assets',
                'Total Assets' => 'total_assets',
                'Accounts Payable' => 'accounts_payable',
                'Current Liabilities' => 'current_liabilities',
                'Non-current Liabilities' => 'non_current_liabilities',
                'Total Liabilities' => 'total_liabilities',
                'Share Capital' => 'share_capital',
                'Retained Earnings' => 'retained_earnings',
                'Current Year Profit' => 'current_year_profit',
                'Total Equity' => 'total_equity',
                'Total Liabilities & Equity' => 'total_liabilities_equity',
            ],
            'cash_flow' => [
                'Opening Cash' => 'opening_cash',
                'Operating' => 'operating',
                'Investing' => 'investing',
                'Financing' => 'financing',
                'Net Cash Change' => 'net_cash_change',
                'Closing Cash' => 'closing_cash',
            ],
        ];

        $rows = [];

        foreach ($definitions[$type] as $label => $key) {
            $rows[] = ['label' => $label, 'amount' => $data[$key] ?? 0];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function subtitle(array $data): string
    {
        $fiscalYear = $data['fiscal_year'] ?? null;
        $period = $data['period'] ?? null;
        $from = $data['from'] ?? null;
        $to = $data['to'] ?? null;

        $parts = [];

        if ($fiscalYear) {
            $parts[] = $fiscalYear->name;
        }

        if ($period) {
            $parts[] = $period->period_name;
        }

        if ($from && $to && $from !== $to) {
            $parts[] = $from.' to '.$to;
        } elseif ($from) {
            $parts[] = 'As of '.$from;
        }

        return implode(' · ', $parts);
    }

    /**
     * Create a unique file name for a given report export.
     *
     * @param  array<string, mixed>  $data
     */
    protected function fileName(string $type, array $data): string
    {
        $fiscalYear = $data['fiscal_year'] ?? null;
        $slug = str($this->titles[$type])->slug().($fiscalYear ? '-'.$fiscalYear->year : '');

        return $slug.'-'.now()->format('YmdHis');
    }
}
