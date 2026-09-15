<?php

namespace App\Services\Accounting;

use App\Models\Budget;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BudgetExportService
{
    /**
     * @var array<int, string>
     */
    protected array $monthLabels = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];

    public function csv(Budget $budget): StreamedResponse
    {
        $fileName = $this->fileName($budget, 'csv');

        return response()->streamDownload(function () use ($budget): void {
            $stream = fopen('php://output', 'w');

            fputcsv($stream, $this->headers(), ',', '"', '\\');

            foreach ($this->rows($budget) as $row) {
                fputcsv($stream, $row, ',', '"', '\\');
            }

            fclose($stream);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function xlsx(Budget $budget): StreamedResponse
    {
        $fileName = $this->fileName($budget, 'xlsx');
        $path = tempnam(sys_get_temp_dir(), 'budget-').'.xlsx';
        $writer = WriterFactory::createFromFile($path);
        $writer->openToFile($path);

        $writer->addRow(Row::fromValues($this->headers()));

        foreach ($this->rows($budget) as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return response()->streamDownload(function () use ($path): void {
            echo file_get_contents($path);
            @unlink($path);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function headers(): array
    {
        return [
            'Account Code',
            'Account Name',
            'Cost Center',
            'Department',
            'Project',
            ...$this->monthLabels,
            'Annual Total',
        ];
    }

    /**
     * @return array<int, array<int, string|float|int|null>>
     */
    protected function rows(Budget $budget): array
    {
        return $budget->lines()
            ->with('account', 'costCenter', 'department', 'project', 'months')
            ->orderBy('id')
            ->get()
            ->map(function ($line): array {
                $months = $line->months->keyBy('month');

                $monthly = [];

                foreach (range(1, 12) as $month) {
                    $monthly[] = (float) ($months->get($month)?->amount ?? 0);
                }

                return [
                    $line->account->account_code,
                    $line->account->account_name,
                    $line->costCenter?->name,
                    $line->department?->name,
                    $line->project?->name,
                    ...$monthly,
                    (float) $line->annual_amount,
                ];
            })
            ->all();
    }

    protected function fileName(Budget $budget, string $extension): string
    {
        return $budget->budget_code.'-'.now()->format('YmdHis').'.'.$extension;
    }
}
