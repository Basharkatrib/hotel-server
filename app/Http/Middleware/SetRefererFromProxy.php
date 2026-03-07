<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to set Referer header from proxy headers when missing.
 * 
 * Problem: When Netlify proxies requests to Railway, mobile browsers
 * (especially Safari iOS) may strip the Referer/Origin headers due to
 * privacy policies (ITP). Without these headers, Sanctum's
 * EnsureFrontendRequestsAreStateful middleware cannot identify the request
 * as coming from a stateful frontend, so it skips session initialization,
 * causing 401 errors for authenticated users on mobile devices.
 * 
 * Solution: Netlify always sends X-Forwarded-Host header when proxying.
 * This middleware uses that header to reconstruct the Referer if it's missing,
 * allowing Sanctum to properly identify the request origin.
 */
class SetRefererFromProxy
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only act if both Referer and Origin are missing
        if (!$request->headers->has('referer') && !$request->headers->has('origin')) {
            $forwardedHost = $request->headers->get('x-forwarded-host');
            
            if ($forwardedHost) {
                // Reconstruct the Referer from the forwarded host
                $scheme = $request->headers->get('x-forwarded-proto', 'https');
                $request->headers->set('referer', $scheme . '://' . $forwardedHost);
            }
        }

        return $next($request);
    }
}
