<x-filament-panels::page>
    <form wire:submit.prevent="refresh" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header px-6 py-4">
            <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">Filters</h2>
        </div>
        <div class="fi-section-content px-6 pb-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="accountId" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Account</label>
                    <select id="accountId" wire:model.live="accountId"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        @foreach ($this->getAccounts() as $account)
                            <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                        @endforeach
                    </select>
                </div>
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
                    <label for="from" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">From</label>
                    <input id="from" type="date" wire:model.live="from"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="to" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">To</label>
                    <input id="to" type="date" wire:model.live="to"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                </div>
                <div>
                    <label for="costCenterId" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Cost center</label>
                    <select id="costCenterId" wire:model.live="costCenterId"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">All</option>
                        @foreach ($this->getCostCenters() as $costCenter)
                            <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="departmentId" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Department</label>
                    <select id="departmentId" wire:model.live="departmentId"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">All</option>
                        @foreach ($this->getDepartments() as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="projectId" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">Project</label>
                    <select id="projectId" wire:model.live="projectId"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">All</option>
                        @foreach ($this->getProjects() as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    @if ($data = $this->getReportData())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                <div>
                    <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">General Ledger</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $data['account']->account_code }} - {{ $data['account']->account_name }}
                        &middot; {{ $data['from'] }} to {{ $data['to'] }}
                    </p>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Entries: {{ count($data['rows']) }}
                </span>
            </div>
            <div class="fi-section-content px-0 pb-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <th class="px-6 py-3 font-medium">Date</th>
                                <th class="px-6 py-3 font-medium">Journal No</th>
                                <th class="px-6 py-3 font-medium">Description</th>
                                <th class="px-6 py-3 text-right font-medium">Debit</th>
                                <th class="px-6 py-3 text-right font-medium">Credit</th>
                                <th class="px-6 py-3 text-right font-medium">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                <td class="px-6 py-2 text-gray-950 dark:text-white" colspan="3">Opening balance</td>
                                <td class="px-6 py-2 text-right tabular-nums text-gray-950 dark:text-white" colspan="2"></td>
                                <td class="px-6 py-2 text-right tabular-nums font-medium text-gray-950 dark:text-white">{{ number_format($data['opening_balance'], 0, ',', '.') }}</td>
                            </tr>
                            @forelse ($data['rows'] as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-6 py-2 whitespace-nowrap text-gray-950 dark:text-white">{{ $row['date'] }}</td>
                                    <td class="px-6 py-2 whitespace-nowrap font-mono text-gray-950 dark:text-white">{{ $row['journal_number'] }}</td>
                                    <td class="px-6 py-2 text-gray-950 dark:text-white">{{ $row['description'] }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['debit'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['credit'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums font-medium text-gray-950 dark:text-white">{{ number_format($row['balance'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No entries in this period.</td>
                                </tr>
                            @endforelse
                            <tr class="border-t border-gray-200 bg-gray-50 font-semibold text-gray-950 dark:border-gray-700 dark:bg-gray-800/50 dark:text-white">
                                <td class="px-6 py-3" colspan="5">Closing balance</td>
                                <td class="px-6 py-3 text-right tabular-nums">{{ number_format($data['closing_balance'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-sm text-gray-500 dark:text-gray-400">Select an account, fiscal year and date range to generate the general ledger.</p>
        </div>
    @endif
</x-filament-panels::page>
