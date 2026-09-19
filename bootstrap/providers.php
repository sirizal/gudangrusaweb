<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AccountingPanelProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\GeographyPanelProvider;
use App\Providers\Filament\ProductsPanelProvider;
use App\Providers\Filament\PurchasingPanelProvider;
use App\Providers\Filament\SalesPanelProvider;
use App\Providers\Filament\WmsPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    AccountingPanelProvider::class,
    GeographyPanelProvider::class,
    ProductsPanelProvider::class,
    SalesPanelProvider::class,
    PurchasingPanelProvider::class,
    WmsPanelProvider::class,
];
