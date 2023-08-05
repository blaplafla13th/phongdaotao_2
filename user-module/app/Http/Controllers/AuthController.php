<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\User\UpdatePassword;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     description="Login",
     *      @OA\Response(
     *          response="200",
     *          description="User credentials",
     *      ),
     *     @OA\RequestBody(
     *     request="LoginRequest",
     *     description="Login",
     *     @OA\JsonContent(
     *     type="object",
    @OA\Property(property="email", type="string", example="example@example.com"),
     *      @OA\Property(property="password", type="string", example="Password@123"),
     *     ),
     *     ),
     * ),
     */
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

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Auth"},
     *     description="Logout",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="User successfully signed out"
     *    ),
     *
     *     ),
     */
    public function logout(): JsonResponse
    {
        Redis::del('Auth:' . request()->bearerToken());
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"Auth"},
     *     description="Refresh token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Token successfully refreshed"
     *   ),
     *     ),
     */
    public function refresh(): JsonResponse
    {
        Redis::del('Auth:' . request()->bearerToken());
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * @OA\Get(
     *     path="/api/auth/user-profile",
     *     tags={"Auth"},
     *     description="Get user profile",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="User profile"
     *  ),
     *),
     */
    public function userProfile()
    {
        return response()->json(json_decode(Redis::get('Auth:' . request()->bearerToken())));
    }

    /**
     * @param $token
     * @return JsonResponse
     */
    protected function createNewToken($token): JsonResponse
    {
        Redis::set('Auth:' . $token,
            json_encode(auth()->user()),
            'EX',
            auth()->factory()->getTTL() * 60);
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/change-password",
     *     tags={"Auth"},
     *     description="Change password",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Password successfully updated"
     *  ),
     *     @OA\RequestBody(
     *     request="UpdatePassword",
     *     description="Change password",
     *     @OA\JsonContent(
     *     type="object",
     *     @OA\Property(property="old_password", type="string", example="Password@123"),
     *     @OA\Property(property="new_password", type="string", example="Aa@123456@aA"),
     *     ),
     *     ),
     *),
     */
    protected function changePassword(UpdatePassword $request): JsonResponse
    {
        Redis::del('Auth:' . request()->bearerToken());
        $userId = User::query()->findOrFail(auth()->id());
        $user = User::query()->where('id', $userId,'password',$request->old_password);
        if (!$user) {
            return response()->json(['message' => 'Password is incorrect']);
        }
        $user->update(['password' => $request->new_password]);
        return response()->json(['message' => 'Password successfully updated']);
    }
}
