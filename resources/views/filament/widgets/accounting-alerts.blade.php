<x-filament-widgets::widget>
    <x-filament::section>
        <h3 class="fi-section-header-heading mb-2 text-base font-semibold leading-6 text-gray-950 dark:text-white">Alerts</h3>

        @php($alerts = $this->getAlerts())

        @forelse ($alerts as $alert)
            <div class="flex items-start justify-between gap-4 py-2">
                <div>
                    <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $alert['title'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $alert['detail'] }}</p>
                </div>
                <span class="shrink-0 text-sm font-medium text-danger-600 dark:text-danger-400">{{ ucfirst($alert['level']) }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">No alerts.</p>
        @endforelse
    </x-filament::section>
</x-filament-widgets::widget>