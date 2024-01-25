<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Activitylog\Facades\CauserResolver;
use App\User;

class SetImpersonationCauser {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        if(session('original_user_id')) {
            $originalUser = User::where('user_id', session('original_user_id'))->first();
            if($originalUser) {
                CauserResolver::setCauser($originalUser);
            }
        }

        return $next($request);
    }
}
