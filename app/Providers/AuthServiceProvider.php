<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // App\Models\Product::class => App\Policies\ProductPolicy::class,
    ];

    public function boot(): void
    {
        // Super-admin bypasses every permission/policy check in the system.
        // This is the ONLY hardcoded role bypass — everything else goes
        // through explicit permissions so access stays auditable.
        Gate::before(function ($user, string $ability) {
            return $user->hasRole(RoleEnum::SUPER_ADMIN->value) ? true : null;
        });
    }
}