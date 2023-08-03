<?php

namespace App\Http\Middleware;
use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
class CheckAdmin
{
    public function handle(Request $request, Closure $next)
    {

        return auth()->user() == null || auth()->user()->role != UserType::Administrator ?
            response()->json(['error' => 'Unauthorized'], 401) : $next($request);
    }
}
