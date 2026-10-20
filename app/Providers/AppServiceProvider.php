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
        // Paksa HTTPS jika request berasal dari secure connection atau reverse proxy / tunnel HTTPS:
        // - request()->isSecure()
        // - Header X-Forwarded-Proto = https (Ngrok, Cloudflare Tunnel, AWS ALB, Nginx Proxy)
        // - Host publik tunnel (ngrok, trycloudflare)
        // - APP_URL menggunakan awalan https://
        $proto = strtolower((string) (request()->header('x-forwarded-proto') ?: request()->server('HTTP_X_FORWARDED_PROTO', '')));
        $sslHeader = strtolower((string) (request()->header('x-forwarded-ssl') ?: request()->server('HTTP_X_FORWARDED_SSL', '')));
        $host = (string) request()->header('host', '');

        $isHttps = request()->isSecure()
            || $proto === 'https'
            || $sslHeader === 'on'
            || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"'))
            || str_contains($host, 'ngrok')
            || str_contains($host, 'trycloudflare.com')
            || (app()->environment('production') && str_starts_with(config('app.url'), 'https://'));

        if ($isHttps) {
            URL::forceScheme('https');
        }
    }
}
