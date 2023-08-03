<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\User\UpdatePassword;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login']]);
    }
    public function login(LoginRequest $request){
        $credentials = $request->only(['email', 'password']);
        if(!$token = auth()->attempt($credentials)){
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (auth()->user()->role === UserType::Banned) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->createNewToken($token);
    }

    public function logout(): JsonResponse
    {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }

    public function refresh(): JsonResponse
    {
        return $this->createNewToken(auth()->refresh());
    }

    public function userProfile(): JsonResponse
    {
        return response()->json(auth()->user());
    }

    public function checkToken(): JsonResponse
    {
        return response()->json([
            "id" => auth()->id(),
            'role' => auth()->user()->role
        ]);
    }

    protected function createNewToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }

    protected function changePassword(UpdatePassword $request): JsonResponse
    {
        $userId = User::query()->findOrFail(auth()->user()->id);
        $user = User::query()->where('id', $userId,'password',$request->old_password);
        if (!$user) {
            return response()->json(['message' => 'Password is incorrect']);
        }
        $user->update(['password' => $request->new_password]);
        return response()->json(['message' => 'Password successfully updated']);
    }
}
