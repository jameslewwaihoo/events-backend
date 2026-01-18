<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Event;
use App\Models\EventAsset;
use App\Models\EventSession;
use App\Models\Guest;
use App\Policies\EventPolicy;
use App\Policies\EventAssetPolicy;
use App\Policies\EventSessionPolicy;
use App\Policies\GuestPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Event::class => EventPolicy::class,
        EventAsset::class => EventAssetPolicy::class,
        EventSession::class => EventSessionPolicy::class,
        Guest::class => GuestPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
