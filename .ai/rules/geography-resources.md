---
paths:
  - 'app/Filament/Geography/Resources/**'
---

# Geography Resources

## Geography resources use drill-down relation managers
The 5-level Indonesia hierarchy (Country>Province>District>SubDistrict>Village) is 5 separate tables with a parent FK. Each parent resource registers a relation manager for its children (CountryResource has ProvincesRelationManager, ProvinceResource has DistrictsRelationManager, etc.) plus independent List/Create/Edit pages. Parent selects show 'code - name'. Village is the only level with postal_code (required, /^\d{5}$/). Panel id 'geography' path '/geography' with viteTheme resources/css/filament/geography/theme.css.
