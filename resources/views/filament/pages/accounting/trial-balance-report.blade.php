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
                    <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">Trial Balance</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $data['fiscal_year']->name }}
                        @if ($data['period'])
                            &middot; {{ $data['period']->period_name }}
                        @endif
                        &middot; {{ $data['from'] }} to {{ $data['to'] }}
                    </p>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Rows: {{ count($data['rows']) }}
                </span>
            </div>
            <div class="fi-section-content px-0 pb-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <th class="px-6 py-3 font-medium">Code</th>
                                <th class="px-6 py-3 font-medium">Account</th>
                                <th class="px-6 py-3 text-right font-medium">Opening Dr</th>
                                <th class="px-6 py-3 text-right font-medium">Opening Cr</th>
                                <th class="px-6 py-3 text-right font-medium">Movement Dr</th>
                                <th class="px-6 py-3 text-right font-medium">Movement Cr</th>
                                <th class="px-6 py-3 text-right font-medium">Closing Dr</th>
                                <th class="px-6 py-3 text-right font-medium">Closing Cr</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($data['rows'] as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-6 py-2 font-mono text-gray-950 dark:text-white">{{ $row['account_code'] }}</td>
                                    <td class="px-6 py-2 text-gray-950 dark:text-white">{{ $row['account_name'] }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['opening_debit'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['opening_credit'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['movement_debit'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['movement_credit'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums font-medium text-gray-950 dark:text-white">{{ number_format($row['closing_debit'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums font-medium text-gray-950 dark:text-white">{{ number_format($row['closing_credit'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No postable accounts.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 bg-gray-50 font-semibold text-gray-950 dark:border-gray-700 dark:bg-gray-800/50 dark:text-white">
                                <td class="px-6 py-3" colspan="2">Total</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ number_format($data['totals']['opening_debit'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ number_format($data['totals']['opening_credit'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ number_format($data['totals']['movement_debit'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ number_format($data['totals']['movement_credit'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ number_format($data['totals']['closing_debit'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ number_format($data['totals']['closing_credit'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if (round($data['totals']['closing_debit'], 2) === round($data['totals']['closing_credit'], 2))
                    <p class="px-6 py-3 text-sm font-medium text-success-600 dark:text-success-400">Balanced: total debit equals total credit.</p>
                @else
                    <p class="px-6 py-3 text-sm font-medium text-danger-600 dark:text-danger-400">Unbalanced: total debit does not equal total credit.</p>
                @endif
            </div>
        </div>
    @else
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400">Select a fiscal year to generate the trial balance.</p>
        </div>
    @endif
</x-filament-panels::page>
