<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class GenerateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $user = Auth::user();
        if ($user) {
            if (!$request->cookie('api_token')) {
                $apiToken = Str::random(60);
                $user->update(['api_token' => $apiToken]);
                $response->headers->setCookie(
                    new Cookie('api_token',
                        $apiToken,
                        Carbon::now()->addRealMinutes(config('session.lifetime'))->getTimestamp(),
                        config('session.path'),
                        config('session.domain'),
                        config('session.secure'),
                        false,
                        false,
                        config('session.same_site') ?? null
                    ));
            }
        }

        return $response;
    }
}