<?php

namespace Database\Seeders;

use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Models\Event;
use App\Models\Post;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Puts a few sample posts into the posts table, and the news of the Athens
 * Marathon: two published posts and a draft linked to the event.
 */
class PostSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Post::factory()->count(5)->create();
        Post::factory()->draft()->create();
        Post::factory()->scheduled()->create();

        $this->seedAthensNews();
    }

    /**
     * The news of the Athens Marathon, written from its race page. Upserted by
     * slug, so running the seeder again updates them.
     */
    private function seedAthensNews(): void
    {
        $event = Event::query()->where('slug', 'athens-marathon')->first();

        if ($event === null) {
            return;
        }

        $posts = [
            'athens-marathon-expo-and-bib-pickup' => [
                'published_at' => '2026-11-02 10:00:00',
                'status' => PostStatus::Published,
                'title' => ['en' => 'Expo opening hours and bib pickup', 'el' => 'Ωράριο έκθεσης και παραλαβή αριθμών'],
                'excerpt' => ['en' => 'The expo at the Faliro Indoor Hall runs from 4 to 7 November; bring your bib email and an ID.', 'el' => 'Η έκθεση στο Κλειστό Φαλήρου λειτουργεί 4–7 Νοεμβρίου· φέρτε το email με τον αριθμό σας και ταυτότητα.'],
                'body' => [
                    'en' => "The allwyn Marathon Expo opens at the Faliro Indoor Hall on Wednesday 4 November.\n\n- Wednesday 4 November: 12:00–21:00\n- Thursday 5 and Friday 6 November: 10:00–21:00\n- Saturday 7 November: 10:00–20:00\n\nBring the email with your bib number and an ID card or passport. No bibs are handed out on race day.",
                    'el' => "Η έκθεση allwyn Marathon Expo ανοίγει στο Κλειστό Φαλήρου την Τετάρτη 4 Νοεμβρίου.\n\n- Τετάρτη 4 Νοεμβρίου: 12:00–21:00\n- Πέμπτη 5 και Παρασκευή 6 Νοεμβρίου: 10:00–21:00\n- Σάββατο 7 Νοεμβρίου: 10:00–20:00\n\nΦέρτε το email με τον αριθμό σας και ταυτότητα ή διαβατήριο. Την ημέρα του αγώνα δεν δίνονται αριθμοί.",
                ],
            ],
            'athens-marathon-buses-to-the-start' => [
                'published_at' => '2026-10-28 10:00:00',
                'status' => PostStatus::Published,
                'title' => ['en' => 'Buses to the start and road closures', 'el' => 'Λεωφορεία για την αφετηρία και κυκλοφοριακές ρυθμίσεις'],
                'excerpt' => ['en' => 'Organiser buses leave central Athens from 05:00; Marathonos Avenue closes from 07:00.', 'el' => 'Τα λεωφορεία της διοργάνωσης αναχωρούν από το κέντρο από τις 05:00· η λεωφ. Μαραθώνος κλείνει από τις 07:00.'],
                'body' => [
                    'en' => "Buses to the start in Marathonas leave between 05:00 and 07:30, depending on your start block.\n\nMarathonos Avenue is closed in both directions from 07:00, so drivers have to be at the start by 07:30.",
                    'el' => "Τα λεωφορεία για την αφετηρία στον Μαραθώνα αναχωρούν από τις 05:00 έως τις 07:30, ανάλογα με το μπλοκ εκκίνησης.\n\nΗ λεωφόρος Μαραθώνος κλείνει και στα δύο ρεύματα από τις 07:00· όσοι έρθουν με αυτοκίνητο πρέπει να είναι στην αφετηρία έως τις 07:30.",
                ],
            ],
            'athens-marathon-results-draft' => [
                'published_at' => null,
                'status' => PostStatus::Draft,
                'title' => ['en' => 'Athens Marathon 2026: the results', 'el' => 'Μαραθώνιος Αθήνας 2026: τα αποτελέσματα'],
                'excerpt' => null,
                'body' => ['en' => 'Results will follow after the race.', 'el' => 'Τα αποτελέσματα θα ακολουθήσουν μετά τον αγώνα.'],
            ],
        ];

        foreach ($posts as $slug => $attributes) {
            $post = Post::query()->updateOrCreate(['slug' => $slug], [
                ...$attributes,
                'category' => PostCategory::News,
                'source_url' => 'https://www.athensauthenticmarathon.gr/en',
            ]);

            $post->events()->syncWithoutDetaching([$event->id]);
        }
    }
}
