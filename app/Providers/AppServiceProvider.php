<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Mail\Mailer;
use App\Mail\GmailTransport;
use Illuminate\Support\Facades\Mail;

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
         \Illuminate\Support\Facades\Mail::extend('gmail', function (array $config = []) {
        return new \App\Mail\GmailTransport();
    });
    }
}
