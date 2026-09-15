---
paths:
  - 'app/Filament/Accounting/Pages/**'
  - 'app/Filament/Accounting/Pages/*Report.php'
  - app/Filament/Accounting/Pages/ImportBudget.php
---

# Pages

## Custom Filament report page conventions
Custom Filament pages: `protected string $view` (NOT static — Page redeclares a non-static $view); declare `$navigationGroup` exactly as `string | UnitEnum | null` or PHP errors on the redeclared type; gate access by overriding `canAccess(): bool` (aborts 403 + hides nav) — not canView. Report pages render a filter form + computed table from HTML/Tailwind, bound with wire:model.live, and compute rows via getReportData() calling the reporting service. Reports derive only from status=posted journal lines.

## Report export actions via ExportsReports trait
Report pages (TrialBalance, GeneralLedger, IncomeStatement, BalanceSheet, CashFlow, BudgetVsActual) use a shared ExportsReports trait that adds CSV/Excel/PDF getHeaderActions() calling ReportExportService with $this->reportType() derived from the class basename (snake), and $this->getReportData(). Custom Filament pages support getHeaderActions() on Page via InteractsWithHeaderActions without extra traits.

## ImportBudget must stay a standalone page for navigation
ImportBudget is a standalone page (Filament\Pages\Page) at app/Filament/Accounting/Pages/, discovered by the accounting panel, routed at /accounting/import-budget, and shows in the Budgeting navigation group. It MUST NOT be registered as a resource sub-page via BudgetResource::getPages() — resource sub-pages are never added to the sidebar navigation, so the Import Budget link disappears. It has a 'Download template' header action (CSV with Account Code/Name, Cost Center, Department, Project, Jan-Dec).

## ImportBudget uses native Filament form components
ImportBudget uses native Filament form components (Select for budget + mode, FileUpload for file) via $this->form with ->statePath('data') — NOT raw HTML controls (raw controls render unstyled). BasePage already includes InteractsWithSchemas, so the page just defines form(Schema $schema). preview() reads the file from $state['file'] (unwrapping the single-file array Livewire stores) and calls BudgetImportService::parse($file->getRealPath()). The page is standalone (Filament\Pages\Page) so it appears in the Budgeting nav; keep it out of BudgetResource::getPages() or the nav item disappears.
