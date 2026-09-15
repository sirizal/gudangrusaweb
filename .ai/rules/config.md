---
paths:
  - config/livewire.php
---

# Config

## Livewire payload and PHP upload limits raised for imports
Livewire payload.max_size is raised to 16MB (default 1MB) to allow geography CSV/XLSX imports (~2MB files). config/livewire.php is published. Matching PHP limits were raised in Herd's php.ini (~/Library/Application Support/Herd/config/php/85/php.ini): upload_max_filesize=16M, post_max_size=20M. Herd Pro is required for 'herd services:restart', so web-server pickup may need a Herd app restart after php.ini edits.
