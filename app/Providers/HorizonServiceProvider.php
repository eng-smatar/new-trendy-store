<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Outside the local environment, the dashboard needs a signed-in user. Every user is an
     * administrator, and the type hint keeps guests out.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (User $user) => true);
    }
}
