<?php

namespace App\Filament\Purchasing\Resources\PurchaseRequests\Concerns;

use App\Models\PurchaseRequestLine;

trait HydratesPurchaseRequestLines
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['lines'] = $this->record->lines->map(fn (PurchaseRequestLine $line): array => [
            'purchase_type' => $line->purchase_type->value,
            'product_id' => $line->product_id,
            'account_id' => $line->account_id,
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => (float) $line->unit_price,
        ])->all();

        return $data;
    }
}
