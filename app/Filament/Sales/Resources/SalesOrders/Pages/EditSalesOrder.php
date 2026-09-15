<?php

namespace App\Filament\Sales\Resources\SalesOrders\Pages;

use App\Enums\SalesOrderStatus;
use App\Filament\Sales\Resources\SalesOrders\Concerns\HandlesSalesOrderWorkflow;
use App\Filament\Sales\Resources\SalesOrders\SalesOrderResource;
use App\Models\SalesOrderLine;
use App\Services\Sales\SalesOrderService;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditSalesOrder extends EditRecord
{
    use HandlesSalesOrderWorkflow;

    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getSalesOrderWorkflowActions(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);

        if ($this->record->status !== SalesOrderStatus::Raised) {
            return $schema->disabled();
        }

        return $schema;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(SalesOrderService::class)->update($record, $data, auth()->user());
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['lines'] = $this->record->lines->map(fn (SalesOrderLine $line): array => [
            'product_id' => $line->product_id,
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => (float) $line->unit_price,
        ])->all();

        return $data;
    }
}
