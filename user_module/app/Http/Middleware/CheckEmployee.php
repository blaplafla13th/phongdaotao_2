<?php

namespace App\Http\Middleware;
use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
class CheckEmployee
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user() === null || !in_array(auth()->user()->role, [UserType::Administrator, UserType::Employee])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
