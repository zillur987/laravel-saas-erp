<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPaymentSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
