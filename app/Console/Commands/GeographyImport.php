<?php

namespace App\Console\Commands;

use App\Services\Geography\GeographyImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('geography:import {file} {level} {--force : Skip the confirmation prompt}')]
#[Description('Bulk-import an Indonesian administrative CSV/XLSX file (province, district, sub_district or village) straight from disk, bypassing HTTP upload limits.')]
class GeographyImport extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GeographyImportService $service): int
    {
        $level = $this->argument('level');

        if (! in_array($level, ['province', 'district', 'sub_district', 'village'], true)) {
            $this->error('Level must be one of: province, district, sub_district, village.');

            return self::INVALID;
        }

        $path = $this->resolvePath($this->argument('file'));

        if (! $path) {
            $this->error('File not found. Provide a path or a filename inside storage/exports.');

            return self::INVALID;
        }

        try {
            $preview = $service->bulkValidate($path, $level);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Parsed '.$preview['valid'].' valid and '.$preview['invalid'].' invalid rows.');

        if ($preview['invalid'] > 0) {
            $this->newLine();
            $this->warn('Invalid rows (first '.min(10, $preview['invalid']).' of '.$preview['invalid'].'):');

            foreach (array_slice($preview['errors'], 0, 10) as $error) {
                $this->warn('  - Row '.$error['line'].' ('.($error['code'] ?: '—').'): '.$error['message']);
            }
        }

        if ($preview['valid'] === 0) {
            $this->error('Nothing to import.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Import '.$preview['valid'].' rows into the '.$level.' table?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($preview['valid']);
        $bar->start();

        try {
            $count = $service->bulkCommit($preview['rows'], $level, function () use ($bar): void {
                $bar->advance(500);
            });
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Imported '.$count.' '.$level.' rows.');

        return self::SUCCESS;
    }

    protected function resolvePath(string $file): ?string
    {
        $candidates = [$file];

        if (! str_starts_with($file, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:\\\/', $file)) {
            $candidates[] = storage_path('exports/'.$file);
            $candidates[] = base_path($file);
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
