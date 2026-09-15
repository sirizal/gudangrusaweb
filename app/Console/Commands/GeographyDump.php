<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('geography:dump {file? : Output path; defaults to storage/exports/geography.json}')]
#[Description('Export the geography tables (countries, provinces, districts, sub_districts, villages) to a JSON file for migrating to another server.')]
class GeographyDump extends Command
{
    /**
     * @var array<string, string>
     */
    protected array $tables = [
        'countries' => 'countries',
        'provinces' => 'provinces',
        'districts' => 'districts',
        'sub_districts' => 'sub_districts',
        'villages' => 'villages',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $path = $this->argument('file') ?? storage_path('exports/geography.json');

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($path, 'w');

        if ($handle === false) {
            $this->error('Could not open '.$path.' for writing.');

            return self::FAILURE;
        }

        $firstTable = true;
        $total = 0;

        fwrite($handle, '{');

        foreach ($this->tables as $table) {
            $count = 0;

            if (! $firstTable) {
                fwrite($handle, ',');
            }

            $firstTable = false;

            fwrite($handle, json_encode($table).':[');

            $firstRow = true;

            DB::table($table)
                ->select(['*'])
                ->orderBy('id')
                ->chunkById(1000, function ($rows) use (&$firstRow, &$count, $handle) {
                    foreach ($rows as $row) {
                        $attributes = (array) $row;
                        unset($attributes['created_by'], $attributes['updated_by']);

                        if (! $firstRow) {
                            fwrite($handle, ',');
                        }

                        $firstRow = false;

                        fwrite($handle, json_encode($attributes, JSON_UNESCAPED_UNICODE));
                        $count++;
                    }
                });

            fwrite($handle, ']');

            $this->info('  '.$table.': '.number_format($count).' rows');
            $total += $count;
        }

        fwrite($handle, '}');
        fclose($handle);

        $this->newLine();
        $this->info('Geography data dumped to '.$path.' ('.number_format($total).' rows).');

        return self::SUCCESS;
    }
}
