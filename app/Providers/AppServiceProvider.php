<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
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
        // Aktifkan forceScheme('https') HANYA jika ada bukti eksplisit
        // dari proxy/reverse proxy bahwa request masuk via HTTPS.
        // JANGAN gunakan heuristik nama domain — terlalu rawan false-positive.
        $isHttps = request()->isSecure()
            || request()->header('X-Forwarded-Proto') === 'https'
            || request()->server('HTTP_X_FORWARDED_PROTO') === 'https'
            || request()->header('X-Forwarded-Ssl') === 'on'
            || request()->server('HTTP_X_FORWARDED_SSL') === 'on'
            || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"'));

        if ($isHttps) {
            URL::forceScheme('https');
        }
    }
}
