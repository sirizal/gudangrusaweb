<?php

namespace App\Providers\Filament;

use App\Filament\Pages\MainDashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('GudangRusa - Admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationItems([
                NavigationItem::make('Products')
                    ->url('/catalog')
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-shopping-bag')
                    ->visible(fn (): bool => auth()->user()?->canAccessPanel(filament()->getPanel('products')) ?? false)
                    ->sort(1),
                NavigationItem::make('Accounting')
                    ->url('/accounting')
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-calculator')
                    ->visible(fn (): bool => auth()->user()?->canAccessPanel(filament()->getPanel('accounting')) ?? false)
                    ->sort(2),
                NavigationItem::make('Sales')
                    ->url('/sales')
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-shopping-cart')
                    ->visible(fn (): bool => auth()->user()?->canAccessPanel(filament()->getPanel('sales')) ?? false)
                    ->sort(3),
                NavigationItem::make('Purchasing')
                    ->url('/purchasing')
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-clipboard-document-list')
                    ->visible(fn (): bool => auth()->user()?->canAccessPanel(filament()->getPanel('purchasing')) ?? false)
                    ->sort(4),
                NavigationItem::make('Geography')
                    ->url('/geography')
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-map')
                    ->visible(fn (): bool => auth()->user()?->canAccessPanel(filament()->getPanel('geography')) ?? false)
                    ->sort(5),
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                MainDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
