---
paths:
  - 'app/Filament/Geography/**'
---

# Filament Geography

## Geography unique indexes must exclude soft-deleted rows
Geography tables (countries, provinces, districts, sub_districts, villages) use soft deletes, so their unique code indexes MUST be partial: CREATE UNIQUE INDEX ... ON ... (col) WHERE deleted_at IS NULL. A full unique constraint rejects re-creating a code that was soft-deleted, even though Laravel's unique validation rule ignores trashed rows — causing UniqueConstraintViolationException at insert. Migration make_geography_unique_indexes_partial converts them. Note: accounting tables (departments, cost_centers, accounts, etc.) share the same full-constraint + soft-delete pattern and would hit the same bug.
