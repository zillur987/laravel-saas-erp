<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Resolve the current tenant from the request's subdomain and bind it
     * into the service container so it's available anywhere via app('tenant').
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost(); 
        $subdomain = explode('.', $host)[0];

        // Skip tenant resolution for the main marketing domain / www / localhost-only setups.
        $reserved = ['www', 'saas-starter', 'localhost', '127'];
        if (in_array($subdomain, $reserved)) {
            return $next($request);
        }

        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (! $tenant) {
            abort(404, 'Tenant not found');
        }

        // Bind the tenant into the container — accessible via app('tenant') anywhere,
        // including inside the BelongsToTenant trait's global scope.
        app()->instance('tenant', $tenant);
        return $next($request);
    }
}