<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redis;

/**
 * @OA\Info(title="User module API", version="0.1"),
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="bearerAuth",
 *     scheme="bearer",
 *     description="Login with email and password to get the authentication token",
 *     in="header",
 *     name="Authorization",
 *     ),
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @OA\Get (
     *     path="/api/clear",
     *     summary="Clear cache",
     *     tags={"Clear"},
     *     @OA\Response(
     *     response="200",
     *     description="Clear cache"
     *    ),
     *     security={{"bearerAuth":{}}}
     *     )
     * )
     *
     * @return void
     */
    protected function clear_cache()
    {
        Redis::del(DepartmentController::$cacheName);
        Redis::del(UserController::$cacheName);
        Redis::del(HistoryController::$cacheNameUsers);
        Redis::del(HistoryController::$cacheNameDepartments);
        Redis::del(HistoryController::$cacheNameNoti);
    }
}
