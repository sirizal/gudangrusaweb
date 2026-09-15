<x-filament-panels::page>
    <form wire:submit="preview" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header px-6 py-4">
            <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">Import budget</h2>
        </div>
        <div class="fi-section-content px-6 pb-6">
            {{ $this->form }}

            <div class="mt-4">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                    Preview import
                </button>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Required columns: Account Code, Cost Center, Department, Project, and the 12 month columns (January – December). Account Name, Description and Annual Total are optional. Preview the file before committing.
            </p>
        </div>
    </form>

    @if ($previewRows !== null && $previewSummary !== null)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                <div>
                    <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">Preview</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $previewSummary['valid'] }} valid
                        &middot; {{ $previewSummary['invalid'] }} with errors
                    </p>
                </div>
                <button type="button" wire:click="import"
                    class="inline-flex items-center justify-center rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-success-500">
                    Import {{ $previewSummary['valid'] }} row{{ $previewSummary['valid'] === 1 ? '' : 's' }}
                </button>
            </div>
            <div class="fi-section-content px-0 pb-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <th class="px-6 py-3 font-medium">Row</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 font-medium">Account</th>
                                <th class="px-6 py-3 font-medium">Cost Center</th>
                                <th class="px-6 py-3 font-medium">Department</th>
                                <th class="px-6 py-3 font-medium">Project</th>
                                <th class="px-6 py-3 text-right font-medium">Annual</th>
                                <th class="px-6 py-3 font-medium">Error</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($previewRows as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-6 py-2 text-gray-500 dark:text-gray-400">{{ $row['line'] }}</td>
                                    <td class="px-6 py-2">
                                        @if ($row['status'] === 'ok')
                                            <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-400/10 dark:text-success-400">Valid</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-danger-50 px-2 py-0.5 text-xs font-medium text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">Error</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-2 font-mono text-gray-950 dark:text-white">{{ $row['account_code'] }}</td>
                                    <td class="px-6 py-2 text-gray-950 dark:text-white">{{ $row['cost_center'] }}</td>
                                    <td class="px-6 py-2 text-gray-950 dark:text-white">{{ $row['department'] }}</td>
                                    <td class="px-6 py-2 text-gray-950 dark:text-white">{{ $row['project'] }}</td>
                                    <td class="px-6 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['annual'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-2 text-danger-600 dark:text-danger-400">
                                        @foreach ($row['errors'] as $error)
                                            <span class="block">{{ $error }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No rows.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>