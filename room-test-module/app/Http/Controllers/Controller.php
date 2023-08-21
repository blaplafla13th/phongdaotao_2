<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redis;

/**
 * @OA\Info(title="Supervisor module API", version="0.1"),
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="bearerAuth",
 *     scheme="bearer",
 *     description="Login with email and password from user module to get the authentication token",
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
        Redis::del(AssignmentController::$cacheName);
        Redis::del(CheckinController::$cacheName);
        Redis::del(RoomDetailController::$cacheName);
        Redis::del(RoomDetailController::$cacheName);
        Redis::del(RoomTestController::$cacheName);
        Redis::del(ShiftController::$cacheName);
    }
}
