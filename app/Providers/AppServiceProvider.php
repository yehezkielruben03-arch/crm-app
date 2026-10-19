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
        $isHttps = request()->isSecure()
            || request()->header('X-Forwarded-Proto') === 'https'
            || request()->server('HTTP_X_FORWARDED_PROTO') === 'https'
            || request()->header('X-Forwarded-Ssl') === 'on'
            || request()->server('HTTP_X_FORWARDED_SSL') === 'on'
            || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"'));

        $isPublicDomain = isset($_SERVER['HTTP_HOST']) && !preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|127\.|localhost)/', $_SERVER['HTTP_HOST']);

        if ($isHttps || $isPublicDomain) {
            URL::forceScheme('https');
        }
    }
}
