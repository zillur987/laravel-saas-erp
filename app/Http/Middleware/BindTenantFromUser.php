<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BindTenantFromUser
{
    /**
     * After JWT auth, bind the user's tenant so BelongsToTenant scopes apply
     * on localhost and other hosts that skip subdomain resolution.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->bound('tenant')) {
            $user = $request->user();
            if ($user?->tenant_id) {
                $tenant = Tenant::query()->find($user->tenant_id);
                if ($tenant) {
                    app()->instance('tenant', $tenant);
                }
            }
        }

        return $next($request);
    }
}
