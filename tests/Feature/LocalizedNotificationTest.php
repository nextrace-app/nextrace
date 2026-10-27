<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocalizedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_links_stay_on_the_locale_they_were_requested_from(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('el.password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            return str_contains($this->mailFor($notification, $user)->actionUrl, '/el/reset-password/');
        });
    }

    public function test_password_reset_links_carry_no_prefix_for_the_default_locale(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $this->mailFor($notification, $user)->actionUrl;

            return str_contains($url, '/reset-password/') && ! str_contains($url, '/el/');
        });
    }

    public function test_verification_links_stay_on_the_locale_the_account_was_created_in(): void
    {
        Notification::fake();

        $this->post(route('el.register'), [
            'name' => 'Nikos',
            'email' => 'nikos@example.com',
            'password' => 'Password!2345',
            'password_confirmation' => 'Password!2345',
        ]);

        $user = User::whereEmail('nikos@example.com')->firstOrFail();

        $this->assertSame('el', $user->locale);

        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
            return str_contains($this->mailFor($notification, $user)->actionUrl, '/el/email/verify/');
        });
    }

    public function test_notifications_follow_the_recipients_preferred_locale(): void
    {
        $user = User::factory()->create(['locale' => 'el']);

        $this->assertSame('el', $user->preferredLocale());

        // Requested from the English side of the site, but the account is Greek:
        // Laravel switches into the recipient's locale while formatting, which
        // is also what puts the '/el' prefix on the link.
        $this->post(route('password.email'), ['email' => $user->email]);

        $body = $this->lastMailBody();

        $this->assertStringContainsString('/el/reset-password/', $body);
        $this->assertStringContainsString('Επαναφορά κωδικού', $body);
    }

    public function test_notifications_are_written_in_the_locale_of_the_request_by_default(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        $body = $this->lastMailBody();

        $this->assertStringContainsString('Reset Password', $body);
        $this->assertStringNotContainsString('/el/reset-password/', $body);
    }

    /**
     * The text body of the last message the array transport collected, i.e. the
     * mail as it was really rendered: locale switching, links and all.
     */
    private function lastMailBody(): string
    {
        $messages = $this->app->make('mailer')->getSymfonyTransport()->messages();

        $this->assertNotEmpty($messages, 'No mail was sent.');

        return (string) $messages[count($messages) - 1]->getOriginalMessage()->getTextBody();
    }

    /**
     * Render a notification the way it is rendered when actually delivered:
     * inside the locale the sender recorded for the recipient.
     */
    private function mailFor(BaseNotification $notification, User $user): MailMessage
    {
        $original = $this->app->getLocale();

        $this->app->setLocale($notification->locale ?? $original);

        $mail = $notification->toMail($user);

        $this->app->setLocale($original);

        return $mail;
    }
}
