---
paths:
  - 'app/Filament/Accounting/Resources/Companies/**'
---

# Companies

## Company profile, dependent geography selects, and relation-manager CreateAction
Company profile (address + geography FKs + tax) lives on the companies table. Geography selects on CompanyForm are a dependent chain (country->province->district->sub_district->village) using ->live(), options(fn(Get $get) => ...) filtered by parent, and afterStateUpdated clearing children + auto-filling postal_code from the village. Documents and BOD are managed via relation managers (CompanyDocumentsRelationManager with a FileUpload on the public disk at company-documents/, CompanyBoardMembersRelationManager). Filament v5 relation managers do NOT auto-register a create action — you MUST add ->headerActions([CreateAction::make()]) to the table or the New button is missing.
