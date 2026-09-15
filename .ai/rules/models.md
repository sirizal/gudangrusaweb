---
paths:
  - app/Models/ProductVariant.php
  - 'app/Models/**'
  - app/Models/FinancialStatementLine.php
  - app/Models/User.php
  - 'app/Models/Company*.php'
---

# Models

## ProductVariant SKU auto-generation convention
Variant SKU is auto-generated on create in format S + 7 running digits (e.g. S0000001) via the creating hook + ProductVariant::generateSku() (max existing numeric suffix incl. trashed + 1, DB unique enforces). Explicit sku is always honored. In the Filament form, sku is disabled + dehydrated(false) and must not be submitted.

## User implements FilamentUser; accounting panel gated by role
User implements Filament\Models\Contracts\FilamentUser. canAccessPanel(): the 'accounting' panel is allowed only for users holding one of the accounting Role enums; the default 'admin' panel returns true. Without FilamentUser, Filament aborts 403 in any non-local env (including testing).

## No timestamps on financial_statement_line_accounts pivot
The `financial_statement_line_accounts` pivot migration has no created_at/updated_at columns. The FinancialStatementLine::accounts() BelongsToMany must NOT call withTimestamps(), otherwise seeding/mapping fails on Postgres with "column created_at does not exist".

## Accounting line/child models skip Blameable and AuditsActivity
Only parent/header models (JournalEntry, Budget, Account, ...) use Blameable/AuditsActivity and carry created_by/updated_by columns. Child line models (JournalEntryLine, BudgetLine, BudgetLineMonth) use only HasFactory and their tables have no blame columns — adding the traits causes "column created_by does not exist". Auditing happens at the parent level.

## Accounting panel gated by role; roles() must use Role model
User::canAccessPanel() gates the 'accounting' panel to users holding an accounting Role enum; the default 'admin' panel stays open (returns true). The User::roles() BelongsToMany must reference the Role MODEL (RoleModel), not the enum — in Laravel/PHP the imported `use App\Enums\Role` would otherwise shadow `Role::class` inside the relation. Do not implement FilamentUser in a way that lets any authenticated user into the accounting panel again.

## Geography panel gated to SuperAdmin only
User::canAccessPanel() has three branches: 'accounting' allows the accounting Role enums; 'geography' allows only SuperAdmin; everything else (default 'admin' panel) returns true. Keep the geography branch restricted to SuperAdmin — it hosts Indonesian administrative reference data.

## Company parent vs document/BOD child model + policy split
Company (parent) uses Blameable + AuditsActivity and its table HAS created_by/updated_by columns (added by add_blame_columns_to_companies_table). Child models CompanyDocument and CompanyBoardMember use only HasFactory (no Blameable/AuditsActivity/SoftDeletes) per the child-model convention. Policies: CompanyDocumentPolicy and CompanyBoardMemberPolicy allow view for staff but create/update/delete only for SuperAdmin, matching CompanyPolicy. Tax fields: npwp (00.000.000.0-000.000), nib, is_pkp, tax_office (KPP), tax_registration_date.
