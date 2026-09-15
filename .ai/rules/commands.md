---
paths:
  - app/Console/Commands/GeographyImport.php
  - 'app/Console/Commands/Geography{Dump,Restore}.php'
---

# Commands

## Use artisan geography:import for full datasets
Full Indonesia geography datasets (83k villages) must be loaded via `php artisan geography:import <file> <level> [--force]`, NOT the browser import page. The command uses GeographyImportService::bulkValidate() (preloads a code=>id parent map, no per-row DB queries) + bulkCommit() (chunked Model::insertOrIgnore, which emits ON CONFLICT DO NOTHING and respects the partial unique indexes — idempotent re-runs). The UI import page stays for small per-province files. Village parent level is sub_district (kecamatan); district parent is province.

## Geography dump/restore for VPS migration
To migrate geography data to a new server: run `php artisan geography:dump` locally (writes storage/exports/geography.json, 91k rows ~18MB), copy that file to the server, then `php artisan geography:restore --fresh`. Restore is idempotent (insertOrIgnore = ON CONFLICT DO NOTHING) and preserves original IDs + parent FKs; --fresh truncates first. created_by/updated_by are stripped from the dump to avoid cross-server user FK issues. Both commands stream JSON (chunked) and set memory_limit to 512M for CLI use.
