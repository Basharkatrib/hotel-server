<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AuthenticateWithCookie
{
    /**
     * Handle an incoming request.
     * 
     * يقرأ التوكن من cookie ويضيفه إلى Authorization header
     * حتى يتمكن Sanctum من قراءته
     */
    public function handle(Request $request, Closure $next): Response
    {
        // إذا كان التوكن موجود في cookie ولكن غير موجود في Authorization header
        if (!$request->bearerToken()) {
            // محاولة قراءة التوكن من cookie
            $token = $request->cookie('auth_token');
            
            if ($token) {
                // إضافة التوكن إلى Authorization header
                $request->headers->set('Authorization', 'Bearer ' . $token);
            } else {
                // للتشخيص: تسجيل أن cookie غير موجودة
                // فقط في حالة الطلبات المصادقة (لتفادي الكثير من الـ logs)
                if ($request->is('api/*') && !$request->is('api/auth/*') && !$request->is('api/hotels') && !$request->is('api/rooms')) {
                    Log::debug('AuthenticateWithCookie: No token found', [
                        'path' => $request->path(),
                        'cookies' => array_keys($request->cookies->all()),
                        'has_auth_token' => $request->hasCookie('auth_token'),
                    ]);
                }
            }
        }

        return $next($request);
    }
}

