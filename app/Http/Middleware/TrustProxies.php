<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Request;

class TrustProxies
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        // تحديد البروكسيات الموثوقة من .env أو '*' للثقة بكل البروكسيات
        $trustedProxies = explode(',', env('TRUSTED_PROXIES', '*'));

        Request::setTrustedProxies(
            $trustedProxies,
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );

        return $next($request);
    }
}