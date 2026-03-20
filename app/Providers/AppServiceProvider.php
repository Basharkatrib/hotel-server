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

    public function boot(): void
    {
        \App\Models\Room::observe(\App\Observers\RoomObserver::class);
        \App\Models\Advertisement::observe(\App\Observers\AdvertisementObserver::class);
        if(env('APP_ENV') !== 'local') {
        URL::forceScheme('https');
        }
    }
}
