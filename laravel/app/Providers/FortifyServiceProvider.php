<?php

namespace App\Providers;

use App\Models\Benutzer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::loginView(fn () => view('production.login'));

        // Anmeldung gegen die Tabelle benutzer. Deaktivierte Konten kommen nicht hinein.
        Fortify::authenticateUsing(function (Request $request) {
            $benutzer = Benutzer::where('email', $request->input(Fortify::username()))->first();

            if ($benutzer && $benutzer->aktiv && Hash::check($request->input('password'), $benutzer->passwort_hash)) {
                return $benutzer;
            }

            return null;
        });

        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
