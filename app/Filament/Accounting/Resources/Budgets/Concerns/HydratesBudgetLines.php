<?php

namespace App\Filament\Accounting\Resources\Budgets\Concerns;

use App\Models\BudgetLine;

trait HydratesBudgetLines
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['lines'] = $this->record->lines->map(
            fn (BudgetLine $line): array => array_merge(
                [
                    'account_id' => $line->account_id,
                    'cost_center_id' => $line->cost_center_id,
                    'department_id' => $line->department_id,
                    'project_id' => $line->project_id,
                    'description' => $line->description,
                ],
                collect(range(1, 12))
                    ->mapWithKeys(fn (int $month): array => ['month_'.$month => (float) ($line->months->firstWhere('month', $month)?->amount ?? 0)])
                    ->all(),
            ),
        )->all();

        return $data;
    }
}
