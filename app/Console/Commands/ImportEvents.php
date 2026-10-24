<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Imports races from a JSON file into the events table as drafts.
 *
 * Re-running it updates the races it already imported, matched by slug, and
 * never publishes anything: new races stay out of the listings until reviewed.
 */
#[Signature('events:import {file=database/data/crete-events.json : JSON file with the races to import}')]
#[Description('Import races from a JSON file as unpublished events')]
class ImportEvents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        /** @var array<int, array<string, mixed>>|null $rows */
        $rows = json_decode((string) file_get_contents($path), true);

        if (! is_array($rows)) {
            $this->error('The file does not contain a JSON list of events.');

            return self::FAILURE;
        }

        $created = $updated = $skipped = 0;

        foreach ($rows as $index => $row) {
            $validator = Validator::make($row, $this->rules());

            if ($validator->fails()) {
                $skipped++;
                $this->warn('Skipped #'.($index + 1).' ('.($row['slug'] ?? 'no slug').'): '.$validator->errors()->first());

                continue;
            }

            /** @var array<string, mixed> $data */
            $data = $validator->validated();

            foreach (config('app.supported_locales') as $locale) {
                foreach (['name', 'description'] as $attribute) {
                    if (! isset($data[$attribute][$locale])) {
                        $this->line("  {$data['slug']}: no {$locale} {$attribute}, needs a translation.");
                    }
                }
            }

            unset($data['source_url']);

            $event = Event::query()->firstOrNew(['slug' => $data['slug']]);
            $exists = $event->exists;

            $event->fill($data)->save();

            $exists ? $updated++ : $created++;
        }

        $this->info("Created {$created}, updated {$updated}, skipped {$skipped}. New events are unpublished.");

        return self::SUCCESS;
    }

    /**
     * The rules every imported race has to satisfy.
     *
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255'],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string'],
            'name.el' => ['nullable', 'string'],
            'description' => ['required', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.el' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'distance_meters' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'city' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:34,42'],
            'longitude' => ['nullable', 'numeric', 'between:19,30'],
            'registration_url' => ['nullable', 'url'],
            'source_url' => ['required', 'url'],
        ];
    }
}
