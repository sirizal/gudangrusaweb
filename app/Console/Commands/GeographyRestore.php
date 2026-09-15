<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('geography:restore {file? : Input path; defaults to storage/exports/geography.json} {--fresh : Truncate the geography tables before restoring}')]
#[Description('Import a geography dump (JSON) created by geography:dump. Inserts rows with their original IDs, skipping any that already exist.')]
class GeographyRestore extends Command
{
    /**
     * @var array<string, class-string>
     */
    protected array $tables = [
        'countries' => Country::class,
        'provinces' => Province::class,
        'districts' => District::class,
        'sub_districts' => SubDistrict::class,
        'villages' => Village::class,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $path = $this->argument('file') ?? storage_path('exports/geography.json');

        if (! is_file($path)) {
            $this->error('Dump file not found: '.$path);

            return self::INVALID;
        }

        $data = json_decode(file_get_contents($path), true);

        if (! is_array($data)) {
            $this->error('The file is not a valid geography dump.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->truncateGeographyTables();
            $this->info('Truncated the geography tables.');
        }

        $total = 0;

        foreach ($this->tables as $table => $model) {
            $rows = $data[$table] ?? [];

            // Filter out rows whose id already exists. This keeps restore
            // deterministic on SQLite, where ON CONFLICT DO NOTHING does not
            // reliably match the partial unique indexes on these tables.
            $existingIds = $model::query()->pluck('id')->flip();
            $newRows = array_values(array_filter(
                $rows,
                fn (array $row): bool => ! isset($existingIds[$row['id']]),
            ));

            foreach (array_chunk($newRows, 500) as $chunk) {
                $model::insert($chunk);
            }

            $this->resetSequence($table);
            $total += count($newRows);

            $this->info('  '.$table.': '.count($newRows).' new rows ('.(count($rows) - count($newRows)).' skipped)');
        }

        $this->newLine();
        $this->info('Restored '.$total.' geography rows from '.$path);

        return self::SUCCESS;
    }

    protected function truncateGeographyTables(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('TRUNCATE countries, provinces, districts, sub_districts, villages RESTART IDENTITY CASCADE');

            return;
        }

        foreach (array_reverse(array_keys($this->tables)) as $table) {
            DB::table($table)->delete();
        }
    }

    protected function resetSequence(string $table): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence(?, 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1))",
                [$table],
            );
        } catch (\Throwable) {
            // Sequence reset is best-effort.
        }
    }
}
