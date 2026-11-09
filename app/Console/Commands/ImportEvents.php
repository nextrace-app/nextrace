<?php

namespace App\Console\Commands;

use App\Support\EventImport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Imports events from a nested JSON file through EventImport, which describes
 * the format and how events are upserted. Each invalid event is skipped with a
 * warning; new events are drafts unless the file publishes them.
 */
#[Signature('events:import {file=database/data/events.json : JSON file with the events to import}')]
#[Description('Import events and their races from a JSON file; new events are unpublished unless the file publishes them')]
class ImportEvents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(EventImport $import): int
    {
        $file = $this->argument('file');
        $path = base_path(is_string($file) ? $file : '');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($path), true);

        if (! is_array($rows) || ! array_is_list($rows)) {
            $this->error('The file does not contain a JSON list of events.');

            return self::FAILURE;
        }

        $created = $updated = $skipped = 0;

        foreach ($rows as $index => $row) {
            $label = 'Skipped #'.($index + 1).' ('.(is_array($row) ? ($row['slug'] ?? 'no slug') : 'not an object').')';

            if (! is_array($row)) {
                $skipped++;
                $this->warn("{$label}: not an object.");

                continue;
            }

            $existing = $import->existingEvent($row);
            $validator = $import->validator($row, $existing === null);

            if ($validator->fails()) {
                $skipped++;
                $this->warn("{$label}: ".$validator->errors()->first());

                continue;
            }

            /** @var array<string, mixed> $data */
            $data = $validator->validated();

            foreach ($import->missingTranslations($data) as $line) {
                $this->line("  {$line}");
            }

            try {
                $import->store($data, $existing);
            } catch (Throwable $exception) {
                $skipped++;
                $this->warn("{$label}: {$exception->getMessage()}");

                continue;
            }

            $existing === null ? $created++ : $updated++;
        }

        $this->info("Created {$created}, updated {$updated}, skipped {$skipped}. New events are unpublished unless the file publishes them.");

        return self::SUCCESS;
    }
}
