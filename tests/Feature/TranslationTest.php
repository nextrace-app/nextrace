<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_errors_are_translated_for_the_default_locale(): void
    {
        $this->post(route('login'), ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'Το πεδίο email είναι υποχρεωτικό.']);
    }

    public function test_validation_errors_are_translated_for_a_prefixed_locale(): void
    {
        $this->post(route('en.login'), ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'The email field is required.']);
    }

    public function test_failed_login_is_reported_in_the_locale_of_the_request(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'Τα στοιχεία σύνδεσης δεν ταιριάζουν με τα δεδομένα μας.']);

        $this->post(route('en.login'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
    }

    public function test_interface_translations_are_shared_with_the_frontend(): void
    {
        $this->get(route('home'))->assertInertia(
            fn ($page) => $page->where('translations.nav.events', 'Εκδηλώσεις')
        );

        $this->get(route('en.home'))->assertInertia(
            fn ($page) => $page->where('translations.nav.events', 'Events')
        );
    }

    public function test_json_strings_are_translated_for_the_default_locale(): void
    {
        $this->app->setLocale('el');

        $this->assertSame('Το προφίλ ενημερώθηκε.', __('Profile updated.'));
    }

    public function test_missing_translations_fall_back_to_english(): void
    {
        $this->app->setLocale('el');

        // No 'el' line exists for this key, so the fallback locale has to answer
        // rather than the key itself being shown to the user.
        $this->assertSame(
            trans('validation.custom.attribute-name.rule-name', [], 'en'),
            __('validation.custom.attribute-name.rule-name')
        );
        $this->assertSame('en', config('app.fallback_locale'));
    }

    /**
     * Adding a locale means adding its language files: a locale that is served
     * but only half translated would fall back to English mid-page.
     */
    public function test_every_supported_locale_ships_the_same_translation_keys(): void
    {
        $reference = $this->translationKeys(config('app.default_locale'));

        $this->assertNotEmpty($reference);

        foreach (config('app.supported_locales') as $locale) {
            $this->assertSame(
                $reference,
                $this->translationKeys($locale),
                "The [{$locale}] language files do not carry the same keys as the default locale."
            );
        }
    }

    /**
     * @return list<string>
     */
    private function translationKeys(string $locale): array
    {
        $keys = [];

        foreach (File::files(lang_path($locale)) as $file) {
            $group = $file->getFilenameWithoutExtension();

            // 'custom' and 'attributes' hold per-language wording rather than
            // messages, so they are expected to differ between locales.
            $lines = Arr::except(require $file->getPathname(), ['custom', 'attributes']);

            foreach (Arr::dot($lines) as $key => $value) {
                $keys[] = "{$group}.{$key}";
            }
        }

        sort($keys);

        return $keys;
    }
}
