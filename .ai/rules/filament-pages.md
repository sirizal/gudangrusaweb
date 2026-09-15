---
paths:
  - app/Filament/Pages/MainDashboard.php
---

# Filament Pages

## Admin panel is the main hub with quick links
The admin panel is the main hub: default panel at /admin, its dashboard is the custom MainDashboard page (app/Filament/Pages/MainDashboard) with quick-link cards to the other panels (products/accounting/geography), each filtered by auth()->user()->canAccessPanel($panel) and linked via $panel->getUrl(). Cross-panel links are also registered as panel navigationItems in AdminPanelProvider (urls /catalog, /accounting, /geography). The admin panel registers viteTheme resources/css/filament/admin/theme.css — required because MainDashboard has a custom Blade view using Tailwind utilities.
