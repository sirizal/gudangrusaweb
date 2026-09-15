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
                    <label for="range" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Range</label>
                    <select id="range" wire:model.live="range"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="monthly">Monthly</option>
                        <option value="ytd">Year to date</option>
                        <option value="full_year">Full year</option>
                    </select>
                </div>
                <div>
                    <label for="periodNumber" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Period</label>
                    <select id="periodNumber" wire:model.live="periodNumber"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Select period</option>
                        @foreach ($this->getPeriods() as $period)
                            <option value="{{ $period->period_number }}">{{ $period->period_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="costCenterId" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Cost center</label>
                    <select id="costCenterId" wire:model.live="costCenterId"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">All cost centers</option>
                        @foreach ($this->getCostCenters() as $costCenter)
                            <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="departmentId" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Department</label>
                    <select id="departmentId" wire:model.live="departmentId"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">All departments</option>
                        @foreach ($this->getDepartments() as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="projectId" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Project</label>
                    <select id="projectId" wire:model.live="projectId"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">All projects</option>
                        @foreach ($this->getProjects() as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    @if ($data = $this->getReportData())
        @if ($data['budget'] && $data['rows'])
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                    <div>
                        <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">Budget vs Actual</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $data['fiscal_year']->name }}
                            &middot; {{ $data['budget']->budget_code }} ({{ $data['budget']->budget_name }})
                            @if ($data['period'])
                                &middot; {{ $data['period']->period_name }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <th class="px-6 py-3 font-medium">Code</th>
                                <th class="px-6 py-3 font-medium">Account</th>
                                <th class="px-6 py-3 text-right font-medium">Budget</th>
                                <th class="px-6 py-3 text-right font-medium">Actual</th>
                                <th class="px-6 py-3 text-right font-medium">Variance</th>
                                <th class="px-6 py-3 text-right font-medium">Variance %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($data['rows'] as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-6 py-2 font-mono text-gray-950 dark:text-white">{{ $row['account_code'] }}</td>
                                    <td class="px-6 py-2 text-gray-950 dark:text-white">{{ $row['account_name'] }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['budget'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['actual'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums @if ($row['variance'] < 0) text-danger-600 dark:text-danger-400 @else text-success-600 dark:text-success-400 @endif">{{ number_format($row['variance'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ $row['variance_pct'] === null ? '—' : $row['variance_pct'] . '%' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 bg-gray-50 font-semibold text-gray-950 dark:border-gray-700 dark:bg-gray-800/50 dark:text-white">
                                <td class="px-6 py-3" colspan="2">Total</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ number_format($data['totals']['budget'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ number_format($data['totals']['actual'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ number_format($data['totals']['variance'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ $data['totals']['variance_pct'] === null ? '—' : $data['totals']['variance_pct'] . '%' }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @else
            <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No approved or active budget exists for this fiscal year.
                </p>
            </div>
        @endif
    @endif
</x-filament-panels::page>