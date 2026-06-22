<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseAuthTokenCookie
{
    /**
     * Allow Sanctum to authenticate API requests from the HttpOnly auth cookie.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = config('app.auth_token_cookie.name', 'auth_token');

        if (! $request->bearerToken() && $request->hasCookie($cookieName)) {
            $request->headers->set('Authorization', 'Bearer '.$request->cookie($cookieName));
        }

        return $next($request);
    }
}
