---
paths:
  - 'app/Filament/Accounting/Resources/**'
---

# Resources

## Workflow actions must live on both Edit and View pages
EditRecord::authorizeAccess() requires can('update'); posted/approved/locked records fail that. Workflow actions (submit/approve/post/reverse/activate/revise) must therefore be present on the View page too, else approvers cannot act. Extract them into a shared Concerns/ trait used by both Edit and View pages.

## Filament v5 table actions come from Filament\Actions\*, not Filament\Tables\Actions\*
In Filament v5 the unified actions live in the Filament\Actions namespace (EditAction, ViewAction, DeleteAction). Importing from Filament\Tables\Actions\* throws "Class not found".

## Plain Repeaters need options() closures, not relationship() Selects
When a Repeater is plain form state (not ->relationship()) and its lines are persisted by a service (e.g. BudgetService), Select relationship() inside it resolves against the container model and errors ("relationship [costCenter] does not exist on Budget"). Use ->options(fn () => ...pluck('name','id')) instead. hydrate existing children manually via mutateFormDataBeforeFill and save via handleRecordUpdate/handleRecordCreation calling the service.
