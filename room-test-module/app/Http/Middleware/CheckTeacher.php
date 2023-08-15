<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;

class CheckTeacher
{
    public function handle(Request $request, Closure $next)
    {
        $user = get_user();
        if ($user === null || !in_array($user->role,
                [UserType::Administrator, UserType::Employee, UserType::Teacher])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
