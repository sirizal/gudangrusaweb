<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MainDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.main-dashboard';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPanelLinks(): array
    {
        $user = auth()->user();
        $panels = filament()->getPanels();

        $links = [];

        foreach ($panels as $panel) {
            if ($panel->getId() === 'admin') {
                continue;
            }

            if (! $user?->canAccessPanel($panel)) {
                continue;
            }

            $links[] = [
                'id' => $panel->getId(),
                'label' => str($panel->getId())->title(),
                'url' => $panel->getUrl(),
                'description' => $this->panelDescription($panel->getId()),
            ];
        }

        return $links;
    }

    protected function panelDescription(string $panelId): string
    {
        return match ($panelId) {
            'products' => 'Manage brands, categories, products, and units.',
            'accounting' => 'Chart of accounts, journals, budgets, and financial reports.',
            'geography' => 'Indonesian administrative regions from country to village.',
            default => 'Manage this module.',
        };
    }
}
