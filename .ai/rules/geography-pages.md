---
paths:
  - 'app/Filament/Geography/Pages/**'
---

# Geography Pages

## Import page uses native Filament form components
ImportGeography uses native Filament form components (Select + FileUpload) via $this->form with ->statePath('data'), NOT raw HTML controls — raw HTML controls look unstyled next to Filament. BasePage already includes InteractsWithSchemas, so pages just define form(Schema $schema). A single FileUpload holds its file as an array in state; preview() unwraps $state['file'][0] to a TemporaryUploadedFile and calls GeographyImportService::parse($file->getRealPath(), $state['level']).
