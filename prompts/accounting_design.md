# Laravel 13 + FilamentPHP 5 — Accounting & Budgeting System Development Prompt

## 1. Project Objective

I want to develop a professional **Accounting & Budgeting System** using:

- Laravel 13
- PHP 8.4+
- FilamentPHP 5
- PostgreSQL
- Livewire
- Eloquent ORM
- Filament Panels
- Laravel Policies / Gates for authorization

The system must be designed as a scalable accounting application for an Indonesian company.

The primary objective is to:

1. Maintain a structured Indonesian Chart of Accounts.
2. Create annual budgets.
3. Input budgets by month.
4. Record actual accounting transactions.
5. Support double-entry bookkeeping.
6. Compare Budget vs Actual.
7. Produce monthly and yearly financial reports.
8. Produce:
   - Income Statement / Profit & Loss
   - Balance Sheet / Statement of Financial Position
   - Cash Flow Statement
   - Budget vs Actual
   - Trial Balance
   - General Ledger
   - Account Ledger
9. Support multiple cost centers / departments.
10. Maintain accounting periods and period closing.
11. Provide a strong foundation for future integration with ERP, purchasing, AP, AR, inventory, and payroll systems.

The system must be designed as a proper accounting engine, not merely a budget tracking application.

---

# 2. Accounting Principles

The system must use **double-entry bookkeeping**.

Every posted accounting transaction must satisfy:

    Total Debit = Total Credit

The system must distinguish between:

- Draft
- Submitted
- Approved
- Posted
- Reversed
- Cancelled

Only POSTED transactions affect financial statements.

Posted accounting transactions must not be directly edited or deleted.

Corrections must be performed through:

- Reversal
- Adjustment Journal
- Correcting Journal

The system must maintain a complete audit trail.

---

# 3. Indonesian Accounting Context

The Chart of Accounts and financial statement structure must be configurable to accommodate Indonesian accounting requirements and **PSAK / SAK Indonesia**.

Do not hard-code PSAK account numbers because every company can have a different detailed Chart of Accounts.

Instead, create:

- Account hierarchy
- Account type
- Account nature
- Normal balance
- Financial statement classification
- Financial statement line mapping

The system should support Indonesian terminology:

### Main Account Types

1. Asset / Aset
2. Liability / Liabilitas
3. Equity / Ekuitas
4. Revenue / Pendapatan
5. Cost of Sales / Harga Pokok Penjualan
6. Expense / Beban
7. Other Income
8. Other Expense

---

# 4. Chart of Accounts

Create a flexible hierarchical Chart of Accounts.

Example:

1000 ASSET
    1100 Current Assets
        1110 Cash
            1111 Petty Cash
            1112 Bank Account
        1120 Accounts Receivable
        1130 Inventory
        1140 Prepaid Expenses

1200 Non-Current Assets
    1210 Fixed Assets
        1211 Land
        1212 Building
        1213 Machinery
        1214 Vehicles
        1215 Office Equipment
    1220 Accumulated Depreciation

2000 LIABILITIES
    2100 Current Liabilities
        2110 Accounts Payable
        2120 Accrued Expenses
        2130 Tax Payable
        2140 Employee Payable

2200 Non-Current Liabilities
    2210 Long Term Loan
    2220 Lease Liability

3000 EQUITY
    3100 Share Capital
    3200 Retained Earnings
    3300 Current Year Profit/Loss

4000 REVENUE
    4100 Sales Revenue
    4200 Service Revenue
    4300 Other Operating Revenue

5000 COST OF SALES
    5100 Cost of Goods Sold
    5200 Direct Cost

6000 OPERATING EXPENSE
    6100 Employee Expense
    6200 Rent Expense
    6300 Utilities
    6400 Transportation
    6500 Office Expense
    6600 Depreciation Expense
    6700 Professional Fees
    6800 IT Expense
    6900 Other Operating Expense

7000 OTHER INCOME
    7100 Interest Income
    7200 Gain on Asset Disposal

8000 OTHER EXPENSE
    8100 Interest Expense
    8200 Loss on Asset Disposal
    8300 Tax Expense

The actual account structure must be configurable through Filament.

---

# 5. Chart of Account Database Design

Create an `accounts` table containing at least:

- id
- parent_id
- account_code
- account_name
- account_name_en
- account_type
- account_sub_type
- normal_balance
- level
- is_group
- is_postable
- is_active
- financial_statement
- financial_statement_line
- cash_flow_category
- cash_flow_activity
- description
- created_by
- updated_by
- timestamps
- soft_deletes if appropriate

Account code must be unique.

Examples:

1000
1100
1110
1111

The system must support unlimited account hierarchy depth.

A GROUP account cannot receive journal postings.

Only POSTABLE accounts can be selected in journal entries.

---

# 6. Financial Statement Mapping

Do not generate financial statements simply based on account code ranges.

Create configurable mapping tables.

For example:

`financial_statement_types`

- income_statement
- balance_sheet
- cash_flow

`financial_statement_lines`

Examples:

Balance Sheet:

- Cash and Cash Equivalents
- Accounts Receivable
- Inventory
- Other Current Assets
- Fixed Assets
- Other Non-Current Assets
- Accounts Payable
- Accrued Liabilities
- Tax Payables
- Loans
- Other Liabilities
- Share Capital
- Retained Earnings

Income Statement:

- Revenue
- Cost of Sales
- Gross Profit
- Operating Expenses
- Operating Profit
- Other Income
- Other Expenses
- Profit Before Tax
- Income Tax
- Net Profit

This allows the company to change reporting structures without changing the accounting engine.

---

# 7. Fiscal Year and Accounting Period

Create:

`fiscal_years`

Fields:

- id
- name
- year
- start_date
- end_date
- status
- is_current

Statuses:

- Open
- Closed

Create:

`accounting_periods`

Fields:

- id
- fiscal_year_id
- period_number
- period_name
- start_date
- end_date
- status

Example:

2026

01 January
02 February
03 March
...
12 December

Period status:

- Open
- Closed

A journal cannot be posted into a closed accounting period.

---

# 8. Budgeting

The budgeting module must support **monthly budgeting**.

A budget is created for a fiscal year.

Example:

Budget 2026

Account: Office Expense

January: Rp 10,000,000
February: Rp 12,000,000
March: Rp 11,000,000
...
December: Rp 15,000,000

Annual budget:

    SUM(January ... December)

Do not store only annual budget values.

Monthly values must be first-class records.

---

# 9. Budget Structure

Create:

`budgets`

Fields:

- id
- budget_code
- budget_name
- fiscal_year_id
- version
- status
- description
- submitted_by
- approved_by
- submitted_at
- approved_at
- created_by
- updated_by
- timestamps

Statuses:

- Draft
- Submitted
- Approved
- Rejected
- Locked

Create:

`budget_lines`

Fields:

- id
- budget_id
- account_id
- cost_center_id
- department_id
- project_id
- description
- annual_amount
- created_at
- updated_at

Create:

`budget_line_months`

Fields:

- id
- budget_line_id
- month
- amount

Month values:

1 = January
2 = February
...
12 = December

Unique constraint:

budget_line_id + month

The annual budget must be calculated from the monthly budget values.

---

# 10. Budget Dimensions

The budgeting engine should support dimensions.

Create configurable dimensions such as:

- Company
- Business Unit
- Department
- Cost Center
- Project
- Branch
- Location

Example:

Account:
6100 Employee Expense

Cost Center:
CC-001 Operations

January:
Rp 100,000,000

February:
Rp 105,000,000

This allows reporting such as:

Budget by:

- Account
- Department
- Cost Center
- Project
- Month
- Year

---

# 11. Budget Approval Workflow

Budget workflow:

Draft
↓
Submitted
↓
Review
↓
Approved / Rejected
↓
Locked

Approved budgets cannot be changed unless a new budget version is created.

Support budget versions:

- Budget 2026 V1
- Budget 2026 V2
- Budget 2026 Revised

Only one version should be marked as the active budget.

---

# 12. General Journal

Create:

`journal_entries`

Fields:

- id
- journal_number
- journal_date
- accounting_period_id
- reference_type
- reference_id
- description
- status
- source
- posted_at
- posted_by
- reversed_journal_id
- created_by
- updated_by
- timestamps

Create:

`journal_entry_lines`

Fields:

- id
- journal_entry_id
- account_id
- cost_center_id
- department_id
- project_id
- description
- debit
- credit
- tax_code_id
- reference
- created_at
- updated_at

Validation:

    SUM(debit) = SUM(credit)

Each journal line must contain either:

- debit > 0

OR

- credit > 0

but not both.

---

# 13. Journal Workflow

Journal lifecycle:

Draft
↓
Submitted
↓
Approved
↓
Posted

Only approved journals can be posted.

After posting:

- Cannot edit
- Cannot delete

Correction must use reversal.

Example:

Original:

Debit Expense 10,000,000
Credit Bank 10,000,000

Reversal:

Debit Bank 10,000,000
Credit Expense 10,000,000

---

# 14. Opening Balance

Create opening balance functionality.

Opening balance must support:

- Asset
- Liability
- Equity

Opening balance journal must be balanced.

Support opening balance by fiscal year.

---

# 15. Budget vs Actual

Create a report:

### Budget vs Actual

Columns:

| Account | Month | Budget | Actual | Variance | Variance % |
|---|---:|---:|---:|---:|---:|

Variance:

For expense:

    Budget - Actual

For revenue:

    Actual - Budget

Also provide configurable variance calculation depending on account type.

Support:

- Monthly
- YTD
- Full Year
- By Department
- By Cost Center
- By Project

---

# 16. General Ledger

Create a General Ledger report.

Filters:

- Date From
- Date To
- Account
- Cost Center
- Department
- Project

Columns:

| Date | Journal No | Description | Debit | Credit | Balance |

Calculate running balance.

Example:

Opening Balance
+ Debit
- Credit
= Closing Balance

Respect each account's normal balance.

---

# 17. Trial Balance

Create Trial Balance.

Columns:

| Account Code | Account Name | Debit | Credit |

Filters:

- Fiscal Year
- Period
- Date

Include:

- Opening Balance
- Period Movement
- Closing Balance

Validation:

    Total Debit = Total Credit

---

# 18. Income Statement

Create an Income Statement / Profit & Loss report.

Example:

Revenue
    Sales Revenue
    Service Revenue
-------------------------
Total Revenue

Cost of Sales
    Cost of Goods Sold
-------------------------
Gross Profit

Operating Expenses
    Employee Expense
    Rent
    Utilities
    Transportation
    Depreciation
-------------------------
Operating Profit

Other Income

Other Expenses
-------------------------
Profit Before Tax

Income Tax
-------------------------
NET PROFIT

The report must support:

- Monthly
- YTD
- Full Year
- Budget vs Actual
- Comparative period
- Comparative year

---

# 19. Balance Sheet

Create Statement of Financial Position / Balance Sheet.

Structure:

ASSETS

Current Assets
    Cash
    Bank
    Accounts Receivable
    Inventory
    Prepaid Expenses

Non-Current Assets
    Fixed Assets
    Accumulated Depreciation

TOTAL ASSETS


LIABILITIES

Current Liabilities
    Accounts Payable
    Accrued Expenses
    Tax Payable

Non-Current Liabilities
    Long Term Loan
    Lease Liability

TOTAL LIABILITIES


EQUITY

Share Capital
Retained Earnings
Current Year Profit/Loss

TOTAL EQUITY

TOTAL LIABILITIES + EQUITY

Validation:

    Total Assets = Total Liabilities + Equity

---

# 20. Cash Flow Statement

The system must support Cash Flow Statement.

Use:

### Operating Activities

### Investing Activities

### Financing Activities

Create configurable cash flow mappings.

Each cash/bank account should be identified as a cash account.

Each journal line involving cash should be classified appropriately.

Support:

- Direct method where feasible
- Indirect method as the primary reporting method

Indirect method example:

Net Profit

Adjustments:
+ Depreciation
+/- Working Capital Changes
+/- Other non-cash items

Cash Flow from Operating Activities

Cash Flow from Investing Activities

Cash Flow from Financing Activities

Net Increase / Decrease in Cash

Opening Cash

Closing Cash

Validation:

    Closing Cash Flow Balance
    =
    Cash & Bank Balance on Balance Sheet

---

# 21. Tax Support

The system should be designed to support Indonesian tax accounting.

Do not hard-code tax rates.

Create configurable:

`tax_codes`

Fields:

- code
- name
- tax_type
- rate
- account_id
- payable_account_id
- receivable_account_id
- is_active

Potential tax types:

- PPN
- PPh 21
- PPh 22
- PPh 23
- PPh 4(2)
- PPh 25
- PPh 29

Tax functionality should be extensible.

---

# 22. Cost Center

Create:

`cost_centers`

Fields:

- code
- name
- parent_id
- manager
- is_active

Support hierarchical cost centers.

Example:

HQ
    Finance
    HR
    Procurement
    IT

Operations
    Site A
    Site B
    Site C

Budget and journal transactions can optionally be assigned to cost centers.

---

# 23. Department

Create:

`departments`

Fields:

- code
- name
- parent_id
- is_active

A journal line and budget line can be associated with a department.

---

# 24. Dashboard

Create a Filament dashboard containing:

### Financial KPIs

- Revenue
- Gross Profit
- Operating Profit
- Net Profit
- Cash Balance
- Accounts Receivable
- Accounts Payable
- Budget Utilization
- YTD Revenue
- YTD Expense

### Charts

- Monthly Revenue
- Monthly Expense
- Monthly Profit
- Budget vs Actual
- Cash Flow Trend

### Alerts

- Over Budget
- Unposted Journals
- Period Closing
- Negative Cash
- Outstanding Approval
- Budget Variance

---

# 25. FilamentPHP Resources

Create FilamentPHP 5 resources for:

### Master Data

- Chart of Accounts
- Fiscal Year
- Accounting Period
- Cost Center
- Department
- Project
- Tax Code
- Cash / Bank Account
- Financial Statement Mapping

### Budgeting

- Budget
- Budget Lines
- Budget Approval
- Budget Versions

### Accounting

- Journal Entry
- Journal Approval
- Opening Balance
- Journal Reversal

### Reports

- Trial Balance
- General Ledger
- Income Statement
- Balance Sheet
- Cash Flow Statement
- Budget vs Actual

---

# 26. Budget Entry UX

The budget entry interface should be optimized for monthly data entry.

Example Filament interface:

Account | Cost Center | Jan | Feb | Mar | Apr | ... | Dec | Total

Example:

Office Expense | FIN-001 | 10M | 12M | 11M | 12M | ... | 15M | 140M

Users should be able to:

- Add account
- Select cost center
- Enter monthly values
- Copy previous month
- Spread annual amount equally
- Spread annual amount based on percentages
- Copy budget from previous year
- Import Excel
- Export Excel

The Total column must be calculated automatically.

---

# 27. Budget Import

Support Excel/CSV import.

Example columns:

Account Code
Account Name
Cost Center
Department
Project
January
February
March
April
May
June
July
August
September
October
November
December

Validate:

- Account exists
- Account is postable
- Cost center exists
- Department exists
- Numeric amounts
- Duplicate budget lines

Provide an import preview before committing.

---

# 28. Currency

The system must support:

- IDR
- Other currencies in the future

Create:

`currencies`

Fields:

- code
- name
- symbol
- decimal_places
- is_base_currency
- is_active

The company should be able to define its base currency.

Default:

IDR

---

# 29. Multi-Company Design

Design the database so it can support multiple companies in the future.

Potentially add:

`companies`

All major accounting entities should optionally belong to a company:

- Accounts
- Budgets
- Journals
- Cost Centers
- Departments
- Fiscal Years

Do not assume that the application will always have only one company.

---

# 30. Audit Trail

Every important accounting action must be auditable.

Track:

- Created by
- Updated by
- Approved by
- Posted by
- Reversed by
- Approved date
- Posted date
- Reversal date

Use Laravel-compatible auditing architecture.

Users must not be able to silently change accounting records.

---

# 31. Permissions

Create role-based authorization.

Suggested roles:

### Super Admin

Full access.

### Finance Manager

- Approve journals
- Approve budgets
- View reports
- Close periods

### Accountant

- Create journals
- Submit journals
- Manage accounting data
- View reports

### Budget Owner

- Create budget
- Submit budget
- View own cost center

### Budget Approver

- Review budget
- Approve / reject budget

### Viewer

Read-only reports.

Use Filament authorization and Laravel Policies.

---

# 32. Accounting Period Closing

Create a month-end closing process.

Checklist:

1. Verify all journals posted.
2. Verify bank transactions.
3. Verify AP.
4. Verify AR.
5. Verify depreciation.
6. Verify tax.
7. Review Trial Balance.
8. Review Income Statement.
9. Review Balance Sheet.
10. Review Cash Flow.
11. Lock period.

Once closed:

No normal journal posting is allowed.

Only authorized users can reopen a period.

All reopening actions must be audited.

---

# 33. Year-End Closing

At year end:

Revenue and Expense accounts should be closed to retained earnings / current year earnings according to the company's accounting policy.

The system should support:

- Year-end closing journal
- Retained earnings calculation
- New fiscal year
- Opening balance carry-forward

Do not automatically post closing entries without an explicit user action and confirmation.

---

# 34. Database Requirements

Use PostgreSQL.

Use proper:

- Foreign keys
- Unique constraints
- Check constraints
- Indexes
- Decimal/numeric fields for money
- Transactions
- Soft deletes only where appropriate

Never use floating-point fields for financial amounts.

Use:

    NUMERIC(20,2)

or another suitable high-precision numeric type.

Never use:

    FLOAT
    DOUBLE

for financial amounts.

---

# 35. Accounting Engine Service Layer

Do not place accounting calculations directly inside Filament pages.

Create dedicated services, for example:

`AccountingService`

Responsibilities:

- Create journal
- Validate journal
- Post journal
- Reverse journal
- Calculate account balance
- Generate trial balance
- Close accounting period

`BudgetService`

Responsibilities:

- Create budget
- Calculate annual budget
- Validate budget
- Submit budget
- Approve budget
- Lock budget
- Calculate budget variance

`FinancialStatementService`

Responsibilities:

- Generate Income Statement
- Generate Balance Sheet
- Generate Cash Flow
- Generate Trial Balance
- Generate General Ledger
- Generate Budget vs Actual

Use service classes and repositories/query objects where appropriate.

---

# 36. Financial Report Calculation

Do not store financial statement totals as manually maintained values.

Financial reports must be generated from:

Journal Entry
+
Journal Entry Lines
+
Chart of Accounts
+
Financial Statement Mapping
+
Accounting Period

Only POSTED journals should be included.

Reports should be generated dynamically.

---

# 37. Reporting Period

All reports must support:

- Date range
- Month
- Quarter
- YTD
- Fiscal year

Examples:

January 2026

Q1 2026

YTD June 2026

FY 2026

---

# 38. Export

Reports should support:

- PDF
- Excel
- CSV

Especially:

- Trial Balance
- General Ledger
- Income Statement
- Balance Sheet
- Cash Flow
- Budget vs Actual

---

# 39. Testing

Create automated tests for accounting integrity.

Minimum tests:

### Journal Balance

A journal cannot be posted if:

    Debit != Credit

### Closed Period

Cannot post journal to closed period.

### Group Account

Cannot post transaction to group account.

### Reversal

Reversal must exactly reverse original journal.

### Trial Balance

Total debit must equal total credit.

### Balance Sheet

Assets must equal:

    Liabilities + Equity

### Cash Flow

Closing cash must reconcile with Balance Sheet cash.

### Budget

Annual budget must equal:

    SUM(monthly budget)

### Posted Journal

Posted journal cannot be directly modified or deleted.

---

# 40. Important Architecture Principle

Separate the system into these layers:

### Master Data

Chart of Accounts
Cost Center
Department
Project
Tax
Currency

↓

### Budgeting

Budget
Budget Lines
Monthly Budget
Budget Approval

↓

### Accounting

Journal
Journal Lines
Posting
Reversal
Period Closing

↓

### Accounting Engine

Trial Balance
Ledger
Account Balance

↓

### Financial Reporting

Income Statement
Balance Sheet
Cash Flow Statement
Budget vs Actual

The financial reports must depend on the accounting engine, not directly on budget records.

---

# 41. Development Approach

Do not generate the entire application in one step.

Develop incrementally in this order:

### Phase 1

Database architecture:

- Companies
- Fiscal Years
- Accounting Periods
- Chart of Accounts
- Cost Centers
- Departments
- Currencies

### Phase 2

Accounting engine:

- Journal
- Journal Lines
- Posting
- Reversal
- Period validation

### Phase 3

Budgeting:

- Budget
- Monthly Budget
- Budget Approval
- Budget Version

### Phase 4

Accounting reports:

- Trial Balance
- General Ledger

### Phase 5

Financial statements:

- Income Statement
- Balance Sheet
- Cash Flow

### Phase 6

Budget analysis:

- Budget vs Actual
- Variance analysis
- Dashboard

### Phase 7

Controls:

- Audit trail
- Permissions
- Period closing
- Year-end closing

### Phase 8

Import / Export:

- Excel
- CSV
- PDF

---

# 42. Coding Standards

Follow Laravel 13 best practices.

Use:

- PHP 8.4+
- Strict typing where appropriate
- Form Requests / validation
- Eloquent relationships
- Enums only for values that are genuinely static; avoid database enums
- Database transactions
- Policies
- Service classes
- Query scopes
- FilamentPHP 5 conventions
- PostgreSQL-compatible migrations

Avoid:

- Business logic inside Blade views
- Business logic inside Filament components
- Direct SQL unless justified
- Floating-point financial calculations
- Hard-coded account codes
- Hard-coded financial statement calculations
- Hard-coded tax rates
- Direct modification of posted journals

---

# 43. Expected Deliverables

For every development phase provide:

1. Database ERD description.
2. PostgreSQL-compatible Laravel migrations.
3. Eloquent models.
4. Model relationships.
5. Factories.
6. Seeders.
7. FilamentPHP 5 Resources.
8. Filament Forms.
9. Filament Tables.
10. Actions.
11. Services.
12. Policies.
13. Validation rules.
14. Automated tests.
15. Example data.
16. Explanation of accounting logic.

Code must be production-oriented and follow Laravel 13 and FilamentPHP 5 conventions.

Before generating code, explain the proposed architecture and database relationships.

Do not make assumptions about Indonesian accounting requirements that are legally or technically uncertain. Where a PSAK treatment depends on the company's accounting policy, make the implementation configurable rather than hard-coded.

The final architecture must be scalable enough to later integrate:

- Accounts Payable
- Accounts Receivable
- Purchasing
- Sales
- Inventory
- Fixed Assets
- Payroll
- Bank Reconciliation
- Tax
- ERP
- Procurement
- WMS

The Accounting Ledger must remain the central source for financial reporting.