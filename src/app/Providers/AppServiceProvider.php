<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \URL::forceRootUrl(config('app.url'));
           
        // Optional: Force HTTPS if you're using SSL
        if (config('app.env') === 'production') {
            \URL::forceScheme('https');
        }
    }
}
