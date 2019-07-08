<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $redir = '/daily_result';
        switch ($guard) {
        case "admin":
            $redir = '/admin/daily_result';
            break;
        default:
            $redir = '/daily_result';
            break;
        }
        if (Auth::guard($guard)->check()) {
        //    return redirect('/daily_result');
            return redirect($redir);
        
        }

        return $next($request);
    }
}
