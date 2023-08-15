<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;

class CheckAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = get_user();
        return $user === null || $user->role != UserType::Administrator ?
            response()->json(['error' => 'Unauthorized'], 401) : $next($request);
    }
}
