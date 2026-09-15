---
paths:
  - 'app/Services/Accounting/**'
  - app/Services/Accounting/BudgetService.php
  - app/Services/Accounting/DashboardService.php
  - 'app/Services/Accounting/*ExportService.php'
  - app/Services/Accounting/BudgetImportService.php
---

# Accounting

## Reverse posted journals via raw DB update, not whereKey()
Posted/reversed JournalEntries are immutable via model updating/deleting hooks (LogicException). To flip `is_reversed` on the original journal during a reversal, AccountingService uses DB::table('journal_entries')->where('id', ...)->update(...) — bypassing the model guard. Use `where('id', ...)`; `whereKey()` exists only on Eloquent Builder, not the raw query builder, and silently fails there.

## Period generation must offset the month by period number
PeriodService::generateForFiscalYear() must add months: $monthStart = $fiscalYear->start_date->copy()->addMonths($month - 1)->startOfMonth(). The original loop reused the fiscal year start date, so all 12 periods got identical Jan dates — silently corrupting period windows for reports (Trial Balance period filtering exposed it).

## Statement service relies on line-code mappings
Statement methods: incomeStatement() falls back to account-type groupings when no FinancialStatementLine mapping exists; balanceSheet() and cashFlowStatement() REQUIRED mappings (BS-CASH, BS-SC, etc.) and return zeros without them. Lookups are by FinancialStatementLine.code only, not statement_type. Test at tests/Feature/Accounting/FinancialStatementReportTest.php uses mapStatementLine() helper to create mappings.

## Budget vs Actual report semantics
budgetVsActual() resolves the budget as the fiscal year's is_active budget, else the latest Approved/Locked version. Actuals come only from status=posted journal lines, signed by each account's normal balance, and must match the budget line's cost_center/department/project dimensions exactly (dimensionKey "acct|cc|dept|proj"). Variance is budget-actual for expense/COS/other-expense and actual-budget for revenue/other-income. Ranges: monthly (one period), ytd (sum months <= period), full_year (annual_amount).

## Balance sheet with a period is cumulative as of that period
balanceSheet() with periodNumber/asOfDate must sum from the fiscal year start, not from the selected period start. resolveReportWindow() gives [periodStart, periodEnd]; balanceSheet overrides reportStart to the FY start date and closes equity with the YTD incomeStatement (asOfDate: reportEnd), not the single period's profit. A period-scoped balance sheet otherwise only reflects that period's movement.

## Budget-vs-actual actuals must filter to budgeted accounts
monthlySignedTotals() must be scoped to the active budget's account_ids when computing budget-vs-actual series and budget utilization. Summing ALL accounts nets a balanced double-entry journal to zero, so actuals would always read 0. Pass $activeBudget?->lines->pluck('account_id')->all() into the DB query.

## Year-end closing resolves equity account via statement mapping
YearEndService::closeFiscalYear() moves the year's net P&L into the current-year-profit equity account. The target account is resolved from the FinancialStatementLine mapping: BS-CYPL wins, BS-RE is the fallback; never hard-code an account code. Throws InvalidArgumentException when no postable/active mapped account exists. Amounts are decimal(20,2); sign the P&L movement by each account's normal balance.

## Year-end closing posts directly and carries forward balanced openings
The closing journal is created directly with status = Posted and source = 'year_end' (bypassing create/approve/post), dated the fiscal year end in period 12. The next fiscal year is created (year+1, is_current = true) with its 12 periods, and only asset/liability/equity net positions are carried forward as OpeningBalance rows (debit OR credit per normal balance — never both). The user posts the opening journal explicitly via the opening balance flow; YearEndService does not auto-post it. Guards: requires all 12 periods present, no unposted journals, and no existing year_end journal.

## Year-end closing posts directly and carries forward via normal balance
YearEndService::closeFiscalYear() posts the closing journal directly (status=Posted, source='year_end', dated FY end in period 12), resolves the current-year-profit equity account via the BS-CYPL statement line mapping (BS-RE fallback, never hardcoded), zeroes every P&L account, and creates the next FY + its 12 periods. Carried-forward OpeningBalances use debit OR credit based on each account's NORMAL balance, never both. The user posts the opening journal explicitly via the opening-balance flow; the service never auto-posts it.

## Report/budget exports: temp file + echo in streamDownload for XLSX/PDF
CSV/XLSX/PDF exports live in ReportExportService and BudgetExportService. openSpout needs a file path with a real extension, so XLSX is written to a tempnam() path with '.xlsx' appended, then echoed inside response()->streamDownload. The streamDownload closure must ECHO the payload (e.g. echo file_get_contents() or echo $pdf->output()); returning a string from the closure produces an empty download. dompdf output is compressed, so assert '%PDF-' prefix + size in tests, never text content.

## Budget import workflow: parse() preview then commit()
BudgetImportService::parse(path) reads CSV/XLSX (openSpout ReaderFactory with MIME fallback for extensionless Livewire temp uploads), validates rows (account exists+postable+non-group, cost center/department/project by code or name, numeric non-negative months, duplicate line key account|cc|dept|proj) and returns preview with a per-row 'status' of ok/error + 'key'. commit() only accepts status=ok rows, in 'upsert' (find-or-create by unique line key) or 'replace' (delete all budget lines first) mode, refuses Approved/Locked budgets, records a budget_import audit. The ImportBudget Filament page (route /accounting/budgets/import) uploads then shows the preview table before the user commits.
