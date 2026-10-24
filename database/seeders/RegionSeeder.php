<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

/**
 * The regions races are filed under: Greece, its administrative regions and
 * the prefectures of Crete.
 */
class RegionSeeder extends Seeder
{
    /**
     * Seed the regions table, which is safe to run again.
     */
    public function run(): void
    {
        foreach (self::regions() as $slug => [$parent, $en, $el]) {
            Region::query()->updateOrCreate(['slug' => $slug], [
                'name' => ['en' => $en, 'el' => $el],
                'parent_id' => $parent === null ? null : Region::query()->where('slug', $parent)->value('id'),
            ]);
        }
    }

    /**
     * The regions by slug as [parent slug, English name, Greek name], parents first.
     *
     * @return array<string, array{0: string|null, 1: string, 2: string}>
     */
    public static function regions(): array
    {
        return [
            'greece' => [null, 'Greece', 'Ελλάδα'],
            'crete' => ['greece', 'Crete', 'Κρήτη'],
            'heraklion' => ['crete', 'Heraklion', 'Ηράκλειο'],
            'chania' => ['crete', 'Chania', 'Χανιά'],
            'rethymno' => ['crete', 'Rethymno', 'Ρέθυμνο'],
            'lasithi' => ['crete', 'Lasithi', 'Λασίθι'],
            'attiki' => ['greece', 'Attica', 'Αττική'],
            'central-macedonia' => ['greece', 'Central Macedonia', 'Κεντρική Μακεδονία'],
            'eastern-macedonia-thrace' => ['greece', 'Eastern Macedonia and Thrace', 'Ανατολική Μακεδονία και Θράκη'],
            'western-macedonia' => ['greece', 'Western Macedonia', 'Δυτική Μακεδονία'],
            'thessaly' => ['greece', 'Thessaly', 'Θεσσαλία'],
            'epirus' => ['greece', 'Epirus', 'Ήπειρος'],
            'central-greece' => ['greece', 'Central Greece', 'Στερεά Ελλάδα'],
            'western-greece' => ['greece', 'Western Greece', 'Δυτική Ελλάδα'],
            'peloponnese' => ['greece', 'Peloponnese', 'Πελοπόννησος'],
            'ionian-islands' => ['greece', 'Ionian Islands', 'Ιόνια Νησιά'],
            'north-aegean' => ['greece', 'North Aegean', 'Βόρειο Αιγαίο'],
            'south-aegean' => ['greece', 'South Aegean', 'Νότιο Αιγαίο'],
        ];
    }
}
