<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class SetSameSiteNone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only modify cookies for API routes
        if (!$request->is('api/*') && !$request->is('sanctum/*')) {
            return $response;
        }

        // Get all cookies from response
        $cookies = $response->headers->getCookies();
        
        // Only modify session and CSRF cookies
        foreach ($cookies as $cookie) {
            $cookieName = $cookie->getName();
            
            // Only modify laravel-session and XSRF-TOKEN cookies
            if (in_array($cookieName, ['laravel_session', 'laravel-session', 'XSRF-TOKEN'])) {
                // Remove existing cookie
                $response->headers->removeCookie(
                    $cookieName,
                    $cookie->getPath(),
                    $cookie->getDomain()
                );
                
                // Re-add with SameSite=None
                $newCookie = new Cookie(
                    $cookieName,
                    $cookie->getValue(),
                    $cookie->getExpiresTime() ?: time() + (1440 * 60), // 24 hours
                    $cookie->getPath() ?: '/',
                    $cookie->getDomain(),
                    false, // secure - false for local development
                    $cookie->isHttpOnly(),
                    false, // raw
                    'none' // sameSite
                );
                $response->headers->setCookie($newCookie);
            }
        }

        return $response;
    }
}

