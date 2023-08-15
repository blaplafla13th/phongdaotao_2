<?php

namespace App\Http\Controllers;

use App\Enums\ActionType;
use App\Models\RoomDetail;
use App\Models\RoomDetailHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class RoomDetailHistoryController extends Controller
{
    // RoomDetail

    /**
     * @OA\Get(
     *     path="/api/history/rooms",
     *     summary="Get list history rooms",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="List history rooms"
     *   ),
     *     @OA\Parameter(
     *     name="room_id",
     *     in="query",
     *     description="Id of room",
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
     *          @OA\Parameter(
     *      name="sortBy",
     *      in="query",
     *      description="Sort by column",
     *      required=false,
     *      @OA\Schema(
     *      type="string",
     *      )
     *      ),
     *      @OA\Parameter(
     *      name="order",
     *      in="query",
     *      description="Sort order",
     *       required=false,
     *      @OA\Schema(
     *      type="string",
     *      )
     *     ),
     * ),
     * @param Request $request
     * @return JsonResponse
     */
    public static $cacheNameRoomDetails = 'room_histories';

    public function showHistoryRoomDetail(Request $request): JsonResponse
    {
        if (!Redis::hexists(RoomDetailHistoryController::$cacheNameRoomDetails, json_encode($request->all()))) {
            $history = RoomDetailHistory::query();
            if ($request->has('room_id')) {
                $history->where('room_id', $request->room_id);
            }
            if (!$request->has('sortBy') || !in_array($request->sortBy, ['id', 'name',
                    'room_id', 'created_at', 'status', 'created_by']))
                $request->sortBy = 'created_at';
            if (!$request->has('order') || !in_array($request->order, ['asc', 'desc']))
                $request->order = 'desc';
            $history =
                $history->orderBy($request->sortBy, $request->order)
                    ->paginate($request->size ?? 10, ['*'], 'page', $request->page ?? 0);
            $response = [
                "data" => $history->items(),
                "current_page" => $history->currentPage(),
                "last_page" => $history->lastPage(),
                "per_page" => $history->perPage(),
                "total" => $history->total()
            ];
            Redis::hset(RoomDetailHistoryController::$cacheNameRoomDetails, json_encode($request->all()), json_encode($response));
        }
        return response()->json(json_decode(Redis::hget(RoomDetailHistoryController::$cacheNameRoomDetails, json_encode($request->all()))));
    }

    /**
     * @OA\Get(
     *     path="/api/history/rooms/{id}",
     *     summary="Get detail history room",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Detail history room"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id of history room",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     *  ),
     *     ),
     * ),
     * @param $id
     * @return JsonResponse
     */
    public function detailHistoryRoomDetail($id): JsonResponse
    {
        if (!Redis::hexists(RoomDetailHistoryController::$cacheNameRoomDetails, $id)) {
            $history = RoomDetailHistory::query()->findOrFail($id);
            Redis::hset(RoomDetailHistoryController::$cacheNameRoomDetails, $id, json_encode($history));
        }
        return response()->json(json_decode(Redis::hget(RoomDetailHistoryController::$cacheNameRoomDetails, $id)));
    }

    /**
     * @OA\Put(
     *     path="/api/history/rooms/{id}",
     *     summary="Restore room",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Restore room"
     *  ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id of history room",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     *  ),
     *     ),
     * ),
     * @param $id
     * @return JsonResponse
     */
    public function restoreRoomDetail($id): JsonResponse
    {
        Redis::del(RoomDetailHistoryController::$cacheNameRoomDetails);
        Redis::del(RoomDetailController::$cacheName);
        $history = RoomDetailHistory::query()->findOrFail($id);
        if ($history->status != ActionType::CREATE)
            RoomDetail::query()->createOrUpdate([
                'id' => $history->room_id
            ], [
                'name' => $history->name
            ]);
        else
            RoomDetail::query()->findOrFail($history->room_id)->delete();
        return response()->json(['message' => 'Restore successfully']);
    }
}
