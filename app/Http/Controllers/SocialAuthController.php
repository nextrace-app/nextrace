<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RememberLocale;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class SocialAuthController extends Controller
{
    /**
     * Send the visitor to Google to sign in.
     */
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Sign in the visitor Google sent back, creating or linking their account.
     */
    public function callback(Request $request): RedirectResponse
    {
        $locale = $this->localeOf($request);

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            // They declined, or the sign-in expired: back to the form they came from.
            return redirect()->route(Locale::routeName('login', $locale));
        }

        $user = $this->resolveUser($google, $locale);

        if ($user === null) {
            return redirect()->route(Locale::routeName('login', $locale))
                ->withErrors(['email' => __('auth.google_unverified')]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route(Locale::routeName('home', $locale)));
    }

    /**
     * The user behind a Google account: the one already linked to it, the one
     * who owns its verified address, or a new one. Null when Google has not
     * verified the address, since that cannot prove who owns it.
     */
    private function resolveUser(ProviderUser $google, string $locale): ?User
    {
        $linked = SocialAccount::query()
            ->where('provider', 'google')
            ->where('provider_id', $google->getId())
            ->first();

        if ($linked !== null) {
            return $linked->user;
        }

        $email = $google->getEmail();

        if ($email === null || ($google->user['email_verified'] ?? false) !== true) {
            return null;
        }

        return DB::transaction(function () use ($google, $email, $locale): User {
            $user = User::query()->where('email', $email)->first() ?? User::create([
                'name' => $google->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'locale' => $locale,
            ]);

            // Google vouches for the address, so it counts as verified.
            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->socialAccounts()->create([
                'provider' => 'google',
                'provider_id' => $google->getId(),
            ]);

            return $user;
        });
    }

    /**
     * The language the visitor was browsing in, remembered from their last page.
     */
    private function localeOf(Request $request): string
    {
        $remembered = $request->cookie(RememberLocale::COOKIE);

        return is_string($remembered) && Locale::isSupported($remembered)
            ? $remembered
            : Locale::default();
    }
}
