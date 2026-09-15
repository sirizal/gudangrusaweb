<x-filament-panels::page>
    <form wire:submit.prevent="refresh" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header px-6 py-4">
            <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">Filters</h2>
        </div>
        <div class="fi-section-content px-6 pb-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="fiscalYearId" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Fiscal year</label>
                    <select id="fiscalYearId" wire:model.live="fiscalYearId"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        @foreach ($this->getFiscalYears() as $fiscalYear)
                            <option value="{{ $fiscalYear->id }}">{{ $fiscalYear->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="periodNumber" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Period</label>
                    <select id="periodNumber" wire:model.live="periodNumber"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Full year</option>
                        @foreach ($this->getPeriods() as $period)
                            <option value="{{ $period->period_number }}">{{ $period->period_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="asOfDate" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">As of date</label>
                    <input id="asOfDate" type="date" wire:model.live="asOfDate"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
            </div>
        </div>
    </form>

    @if ($data = $this->getReportData())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                <div>
                    <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">Balance Sheet</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $data['fiscal_year']->name }}
                        @if ($data['period'])
                            &middot; {{ $data['period']->period_name }}
                        @endif
                        &middot; as of {{ $data['to'] }}
                    </p>
                </div>
                @if ($data['balanced'])
                    <span class="text-sm font-medium text-success-600 dark:text-success-400">Balanced</span>
                @else
                    <span class="text-sm font-medium text-danger-600 dark:text-danger-400">Difference: {{ number_format($data['difference'], 0, ',', '.') }}</span>
                @endif
            </div>
            <div class="fi-section-content px-6 pb-6">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">Assets</h3>
                        <table class="w-full text-left text-sm">
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr>
                                    <td class="px-3 py-2">Current assets</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($data['current_assets'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">Non-current assets</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($data['non_current_assets'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="border-t border-gray-200 font-semibold text-gray-950 dark:border-gray-700 dark:text-white">
                                    <td class="px-3 py-3">Total assets</td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ number_format($data['total_assets'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">Liabilities &amp; Equity</h3>
                        <table class="w-full text-left text-sm">
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr>
                                    <td class="px-3 py-2">Current liabilities</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($data['current_liabilities'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">Non-current liabilities</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($data['non_current_liabilities'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">Total liabilities</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($data['total_liabilities'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">Share capital</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($data['share_capital'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">Retained earnings</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($data['retained_earnings'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">Current-year profit</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($data['current_year_profit'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2">Total equity</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($data['total_equity'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="border-t border-gray-200 font-semibold text-gray-950 dark:border-gray-700 dark:text-white">
                                    <td class="px-3 py-3">Total liabilities &amp; equity</td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ number_format($data['total_liabilities_equity'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400">Select a fiscal year to generate the balance sheet.</p>
        </div>
    @endif
</x-filament-panels::page>