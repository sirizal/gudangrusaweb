<?php

namespace App\Filament\Accounting\Pages\Concerns;

use App\Services\Accounting\ReportExportService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsReports
{
    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export-csv')
                ->label('CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => app(ReportExportService::class)->csv($this->reportType(), $this->getReportData())),

            Action::make('export-xlsx')
                ->label('Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => app(ReportExportService::class)->xlsx($this->reportType(), $this->getReportData())),

            Action::make('export-pdf')
                ->label('PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->action(fn (): StreamedResponse => app(ReportExportService::class)->pdf($this->reportType(), $this->getReportData())),
        ];
    }

    protected function reportType(): string
    {
        return str($this::class)->classBasename()
            ->replaceEnd('Report', '')
            ->snake()
            ->toString();
    }
}
