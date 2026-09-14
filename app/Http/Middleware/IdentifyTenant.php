<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Resolve the current tenant from subdomain or X-Tenant header and bind it
     * into the service container so it's available anywhere via app('tenant').
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $subdomain = $request->header('X-Tenant') ?: explode('.', $host)[0];

        $reserved = ['www', 'saas-starter', 'localhost', '127'];
        if (in_array($subdomain, $reserved, true) || $subdomain === '') {
            return $next($request);
        }

        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (! $tenant) {
            abort(404, 'Tenant not found');
        }

        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
