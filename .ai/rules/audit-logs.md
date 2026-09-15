---
paths:
  - 'app/Filament/Accounting/Resources/AuditLogs/**'
---

# Audit Logs

## Audit log resource is read-only; v5 actions namespace
AuditLogResource is a read-only viewer: AuditLogPolicy allows viewAny/view only for staff (create/update/delete always false), and the resource has no Create/Edit pages. The Filament v5 table actions in this project come from Filament\\Actions\\* (e.g. ViewAction), NOT Filament\\Tables\\Actions\\*. The 'changes' JSON column renders pretty-printed via TextEntry with TextSize::Small — use Filament\\Support\\Enums\\TextSize, not a TextEntry/TextEntrySize class.
