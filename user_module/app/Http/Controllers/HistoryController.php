<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentHistory;
use App\Models\Notify;
use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class HistoryController extends Controller
{
    // User
    /**
     * @OA\Get(
     *     path="/api/history/users",
     *     summary="Get list history users",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="List history users"
     *    ),
     *     @OA\Parameter(
     *     name="id",
     *     in="query",
     *     description="Id of user",
     *     required=false,
     *     @OA\Schema(
     *     type="integer"
     *   ),
     *     ),
     *     @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Page number",
     *     required=false,
     *     @OA\Schema(
     *     type="integer"
     *  ),
     *     ),
     *     @OA\Parameter(
     *     name="size",
     *     in="query",
     *     description="Number of items per page",
     *     required=false,
     *     @OA\Schema(
     *     type="integer"
     *  ),
     *     ),
     * @OA\Parameter(
     *       name="orderBy",
     *       in="query",
     *       description="Sort field",
     *       required=false,
     *       @OA\Schema(
     *       type="string"
     *    ),
     *       ),
     * @OA\Parameter(
     *       name="order",
     *       in="query",
     *       description="Sort direction: asc or desc",
     *       required=false,
     *       @OA\Schema(
     *       type="string"
     *    ),
     *       ),
     * ),
     *
     * @param Request $request
     * @return JsonResponse
     */
    public static $cacheNameUsers = 'user_histories';

    public function showHistoryUser(Request $request): JsonResponse
    {
        if (!Redis::hexists(HistoryController::$cacheNameUsers, json_encode($request->all()))) {
            $history = UserHistory::query();
            if ($request->has('id'))
                $history = $history->where('user_id', $request->id);
            if (!in_array($request->order, ['asc', 'desc']))
                $request->order = 'desc';
            if (!in_array($request->orderBy, ['id', 'user_id', 'name', 'email', 'password', 'phone', 'role', 'department_id', 'created_at', 'status', 'created_by']))
                $request->orderBy = 'created_at';
            $history = $history->orderBy($request->orderBy, $request->order);
            $history =
                $history->paginate($request->size ?? 10, ['*'], 'page', $request->page ?? 0);
            Redis::hset(HistoryController::$cacheNameUsers, json_encode($request->all()), json_encode($history));
        }
        return response()->json(json_decode(Redis::hget(HistoryController::$cacheNameUsers, json_encode($request->all()))));
    }

    /**
     * @OA\Get(
     *     path="/api/history/users/{id}",
     *     summary="Get detail history user",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Detail history user"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id of history user",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     *     ),
     *     ),
     * ),
     * @param $id
     * @return JsonResponse
     */
    public function detailHistoryUser($id): JsonResponse
    {
        if (!Redis::hexists(HistoryController::$cacheNameUsers, $id)) {
            $history = UserHistory::query()->findOrFail($id);
            Redis::hset(HistoryController::$cacheNameUsers, $id, json_encode($history));
        }
        return response()->json(json_decode(Redis::hget(HistoryController::$cacheNameUsers, $id)));
    }

    /**
     * @OA\Put(
     *     path="/api/history/users/{id}",
     *     summary="Restore user",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Restore user"
     *  ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id of history user",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     *    ),
     *     ),
     * ),
     * @param $id
     * @return JsonResponse
     */
    public function restoreUser($id): JsonResponse
    {
        Redis::del(UserController::$cacheName);
        Redis::del(HistoryController::$cacheNameUsers);
        $history = UserHistory::query()->findOrFail($id);
        User::query()->updateOrCreate([
            'id' => $history->user_id
        ], [
            'name' => $history->name,
            'email' => $history->email,
            'phone' => $history->phone,
            'role' => $history->role,
            'department_id' => $history->department_id,
            'password' => $history->password,
        ]);
        return response()->json(['message' => 'Restore successfully']);
    }

    // Department

    /**
     * @OA\Get(
     *     path="/api/history/departments",
     *     summary="Get list history departments",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="List history departments"
     *   ),
     *     @OA\Parameter(
     *     name="department_id",
     *     in="query",
     *     description="Id of department",
     *     required=false,
     *     @OA\Schema(
     *     type="integer"
     *  ),
     *     ),
     *          @OA\Parameter(
     *      name="page",
     *      in="query",
     *      description="Page number",
     *      required=false,
     *      @OA\Schema(
     *      type="integer"
     *   ),
     *      ),
     *      @OA\Parameter(
     *      name="size",
     *      in="query",
     *      description="Number of items per page",
     *      required=false,
     *      @OA\Schema(
     *      type="integer"
     *   ),
     *      ),
     *      @OA\Parameter(
     *      name="orderBy",
     *      in="query",
     *      description="Sort field",
     *      required=false,
     *      @OA\Schema(
     *      type="string"
     *   ),
     *      ),
     *      @OA\Parameter(
     *      name="order",
     *      in="query",
     *      description="Sort direction: asc or desc",
     *      required=false,
     *      @OA\Schema(
     *      type="string"
     *   ),
     *      ),
     * ),
     * @param Request $request
     * @return JsonResponse
     */
    public static $cacheNameDepartments = 'department_histories';
    public function showHistoryDepartment(Request $request): JsonResponse
    {
        if (!Redis::hexists(HistoryController::$cacheNameDepartments, json_encode($request->all()))) {
            $history = DepartmentHistory::query();
            if ($request->has('department_id'))
                $history->where('department_id', $request->department_id);
            if (!in_array($request->order, ['asc', 'desc']))
                $request->order = 'desc';
            if (!in_array($request->orderBy, ['id', 'department_id', 'name', 'created_at', 'status', 'created_by']))
                $request->orderBy = 'created_at';
            $history = $history->orderBy($request->orderBy ?? 'created_at', $request->order);
            $history =
                $history->paginate($request->size ?? 10, ['*'], 'page', $request->page ?? 0);
            Redis::hset(HistoryController::$cacheNameDepartments, json_encode($request->all()), json_encode($history));
        }
        return response()->json(json_decode(Redis::hget(HistoryController::$cacheNameDepartments, json_encode($request->all()))));
    }

    /**
     * @OA\Get(
     *     path="/api/history/departments/{id}",
     *     summary="Get detail history department",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Detail history department"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id of history department",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     *  ),
     *     ),
     * ),
     * @param $id
     * @return JsonResponse
     */
    public function detailHistoryDepartment($id): JsonResponse
    {
        if (!Redis::hexists(HistoryController::$cacheNameDepartments, $id)) {
            $history = DepartmentHistory::query()->findOrFail($id);
            Redis::hset(HistoryController::$cacheNameDepartments, $id, json_encode($history));
        }
        return response()->json(json_decode(Redis::hget(HistoryController::$cacheNameDepartments, $id)));
    }

    /**
     * @OA\Put(
     *     path="/api/history/departments/{id}",
     *     summary="Restore department",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Restore department"
     *  ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id of history department",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     *  ),
     *     ),
     * ),
     * @param $id
     * @return JsonResponse
     */
    public function restoreDepartment($id): JsonResponse
    {
        Redis::del(HistoryController::$cacheNameDepartments);
        Redis::del(DepartmentController::$cacheName);
        $history = DepartmentHistory::query()->findOrFail($id);
        Department::query()->createOrUpdate([
            'id' => $history->department_id
        ], [
            'name' => $history->name
        ]);
        return response()->json(['message' => 'Restore successfully']);
    }

    //Notification

    /**
     * @OA\Get(
     *     path="/api/history/notifications",
     *     summary="Get list history notifications",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="List history notifications"
     *  ),
     *     @OA\Parameter(
     *     name="id",
     *     in="query",
     *     description="Id of history notification",
     *     required=false,
     *     @OA\Schema(
     *     type="integer"
     *    ),
     *     ),
     *          @OA\Parameter(
     *      name="page",
     *      in="query",
     *      description="Page number",
     *      required=false,
     *      @OA\Schema(
     *      type="integer"
     *   ),
     *      ),
     *      @OA\Parameter(
     *      name="size",
     *      in="query",
     *      description="Number of items per page",
     *      required=false,
     *      @OA\Schema(
     *      type="integer"
     *   ),
     *      ),
     *@OA\Parameter(
     *      name="orderBy",
     *      in="query",
     *      description="Sort by column",
     *      required=false,
     *      @OA\Schema(
     *      type="string",
     *      )
     *   ),
     * @OA\Parameter(
     *      name="order",
     *      in="query",
     *      description="Sort order: asc or desc",
     *      required=false,
     *      @OA\Schema(
     *      type="string",
     *      )
     *   ),
     * ),
     * @param Request $request
     * @return JsonResponse
     */
    public static $cacheNameNoti = 'notifications';

    public function showHistoryNotification(Request $request): JsonResponse
    {
        if (!Redis::hexists(HistoryController::$cacheNameNoti, json_encode($request->all()))) {
            $history = Notify::query();
            if ($request->has('id'))
                $history = $history->where('to', $request->id);
            if (!in_array($request->order, ['asc', 'desc']))
                $request->order = 'desc';
            if (!in_array($request->orderBy, ['id', 'to', 'from', 'title', 'content', 'created_at', 'status', 'created_by', 'address']))
                $request->orderBy = 'created_at';
            $history = $history->orderBy($request->orderBy ?? 'created_at', $request->order);
            $history = $history->paginate($request->size ?? 10, ['*'], 'page', $request->page ?? 0);
            Redis::hset(HistoryController::$cacheNameNoti, json_encode($request->all()), json_encode($history));
        }
        return response()->json(json_decode(Redis::hget(HistoryController::$cacheNameNoti, json_encode($request->all()))));
    }
}
