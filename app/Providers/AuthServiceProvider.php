<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Policies\BookingPolicy;
use App\Policies\HotelPolicy;
use App\Policies\RoomPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => \App\Policies\UserPolicy::class,
        Hotel::class => HotelPolicy::class,
        Room::class => RoomPolicy::class,
        Booking::class => BookingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}

