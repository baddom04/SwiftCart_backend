<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        URL::forceScheme("http");
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
