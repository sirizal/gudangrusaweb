---
paths:
  - 'app/Services/Geography/**'
---

# Geography

## GeographyImportService parses then commits per level
GeographyImportService::parse(path, level) reads CSV/XLSX (openSpout with MIME fallback), maps headers (code/name/parent_code/postal_code/country_code — include underscore variants in headerMap), validates parent existence and 5-digit postal codes for villages, and returns a preview. commit() upserts by (parent_id, code). Provinces default country to IDN. Header labels must include underscore spellings ('parent_code', 'postal_code') not just spaced forms.
