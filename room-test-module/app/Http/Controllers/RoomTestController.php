<?php

namespace App\Http\Controllers;

use App\Http\Requests\Test\StoreRequest;
use App\Http\Requests\Test\UpdateRequest;
use App\Models\RoomTest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class RoomTestController extends Controller
{
    public static $cacheName = 'room_tests';

    /**
     * @OA\Get(
     * path="/api/tests",
     * summary="Get list rooms use in test",
     * tags={"RoomTest"},
     * @OA\Response(
     * response="200",
     * description="List rooms use in test"
     * ),
     * @OA\Parameter(
     * name="room",
     * in="query",
     * description="Room name",
     * required=false,
     * @OA\Schema(
     * type="string"
     * ),
     * ),
     * @OA\Parameter(
     * name="shift_id",
     * in="query",
     * description="Shift id",
     * required=false,
     * @OA\Schema(
     * type="string"
     * ),
     *     ),
     *     @OA\Parameter(
     *     name="from",
     *     in="query",
     *     description="From date",
     *     required=false,
     *     @OA\Schema(
     *     type="string",
     *     format="date-time"
     *     )
     *),
     *     @OA\Parameter(
     *     name="to",
     *     in="query",
     *     description="To date",
     *     required=false,
     *     @OA\Schema(
     *     type="string",
     *     format="date-time"
     *     )
     *),
     *@OA\Parameter(
     *name="page",
     *in="query",
     *description="Page number",
     *required=false,
     *@OA\Schema(
     *type="integer"
     *),
     *),
     *@OA\Parameter(
     *name="size",
     *in="query",
     *description="Number of items per page",
     *required=false,
     *@OA\Schema(
     *type="integer"
     *),
     *),
     *@OA\Parameter(
     *name="sortBy",
     *in="query",
     *description="Sort by column",
     *required=false,
     *@OA\Schema(
     *type="string",
     *)
     *),
     *@OA\Parameter(
     *name="order",
     *in="query",
     *description="Sort order",
     *required=false,
     *@OA\Schema(
     *type="string",
     *)
     *),
     *),
     *     ),
     *
     *Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if (!Redis::hexists(RoomTestController::$cacheName, json_encode($request->all()))) {
            $rooms = RoomTest::query();
            $rooms = $rooms->leftJoin(DB::raw("checkins s1"), DB::raw("s1.supervisor"), "=", DB::raw("room_tests.supervisor1"));
            $rooms = $rooms->leftJoin(DB::raw("checkins s2"), DB::raw("s2.supervisor"), "=", DB::raw("room_tests.supervisor2"));
            $rooms = $rooms->leftJoin(DB::raw("checkins s3"), DB::raw("s3.supervisor"), "=", DB::raw("room_tests.supervisor3"));
            $rooms = $rooms->leftJoin(DB::raw("room_details"), DB::raw("room_details.id"), "=", DB::raw("room_tests.room_detail_id"));
            $rooms = $rooms->leftJoin(DB::raw("shifts"), DB::raw("shifts.id"), "=", DB::raw("room_tests.shift_id"));

            if ($request->has('room'))
                $rooms = $rooms->where("room_details.name", "ilike", '%' . $request->room . '%');

            if ($request->has('shift_id'))
                $rooms = $rooms->where("shift_id", $request->shift_id);

            if ($request->has('from'))
                $rooms = $rooms->where("shift_start_time", ">=", Carbon::parse($request->from)->format('Y/m/d H:i:s'));

            if ($request->has('to'))
                $rooms = $rooms->where("shift_start_time", "<=", Carbon::parse($request->to)->format('Y/m/d H:i:s'));

            if (!$request->has('orderBy') || !in_array($request->orderBy, ['id', 'room', "quantity",
                    "need_supervisor,shift_start_time"]))
                $request->orderBy = "shift_start_time";

            if (!$request->has('order') || !in_array($request->orderType, ['asc', 'desc']))
                $request->orderType = "desc";

            $rooms = $rooms->orderBy($request->orderBy, $request->orderType)
                ->paginate($request->size ?? 10,
                    [DB::raw('room_tests.id as id'), 'room_details.name as room', "quantity", "need_supervisor",
                        DB::raw("shifts.shift_start_time as start_time"), DB::raw("s1.supervisor as supervisor1"),
                        DB::raw("s2.supervisor as supervisor2"), DB::raw("s3.supervisor as supervisor3"),
                        "exam_test_id"],
                    'page', $request->page ?? 0);
            $response = [
                "data" => $rooms->items(),
                "current_page" => $rooms->currentPage(),
                "last_page" => $rooms->lastPage(),
                "per_page" => $rooms->perPage(),
                "total" => $rooms->total()
            ];
            Redis::hset(RoomTestController::$cacheName, json_encode($request->all()), json_encode($response));
        }
        return response()->json(json_decode(Redis::hget(RoomTestController::$cacheName, json_encode($request->all()))));
//        return response()->json($response);
    }

    /**
     * @OA\Post(
     *    path="/api/tests",
     *   summary="Create room for test",
     *  tags={"RoomTest"},
     * security={{"bearerAuth":{}}},
     *  @OA\Response(
     *     response="200",
     *     description="Room successfully created"
     * ),
     * @OA\RequestBody(
     *      description="Update room",
     *      required=true,
     *       @OA\JsonContent(
     *      required={"exam_test_id","room_detail_id","quantity","need_supervisor","shift_id"},
     *      @OA\Property(
     *      property="exam_test_id",
     *      type="integer"
     *     ),
     *      @OA\Property(
     *      property="room_detail_id",
     *      type="integer"
     *    ),
     *      @OA\Property(
     *      property="quantity",
     *      type="integer"
     *   ),
     *      @OA\Property(
     *      property="need_supervisor",
     *      type="integer"
     *  ),
     *      @OA\Property(
     *      property="shift_id",
     *      type="integer"
     *  ),
     *    ),
     *     ),
     *)
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */

    public function store(StoreRequest $request): JsonResponse
    {
        Redis::del(RoomTestController::$cacheName);
        $data = array_merge($request->validated(), ["last_edited" => get_user()->id]);
        RoomTest::query()->create($data);
        return response()->json(['message' => 'Room successfully created']);
    }

    /**
     * @OA\Get(
     *     path="/api/tests/{id}",
     *     summary="Get room by id",
     *     tags={"RoomTest"},
     *     @OA\Response(
     *     response="200",
     *     description="Get room"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *      in="path",
     *     description="Id of room",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     * ),
     *     ),
     *   )
     *
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        if (!Redis::hexists(RoomTestController::$cacheName, $id)) {
            $room = RoomTest::query()->findOrFail($id);
            Redis::hset(RoomTestController::$cacheName, $id, json_encode($room));
        }
        return response()->json(json_decode(Redis::hget(RoomTestController::$cacheName, $id)));
    }


    /**
     * @OA\Put(
     *     path="/api/tests/{id}",
     *     summary="Update room",
     *     tags={"RoomTest"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Room successfully updated"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *      in="path",
     *     description="Id of room",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     * ),
     *     ),
     *     @OA\RequestBody(
     *     description="Update room",
     *     required=true,
     *      @OA\JsonContent(
     *     required={"exam_test_id","room_detail_id","quantity","need_supervisor","shift_id"},
     *     @OA\Property(
     *     property="exam_test_id",
     *     type="integer"
     *    ),
     *     @OA\Property(
     *     property="room_detail_id",
     *     type="integer"
     *   ),
     *     @OA\Property(
     *     property="quantity",
     *     type="integer"
     *  ),
     *     @OA\Property(
     *     property="need_supervisor",
     *     type="integer"
     * ),
     *     @OA\Property(
     *     property="shift_id",
     *     type="integer"
     * ),
     *     ),
     *     ),
     *  ),
     *
     *
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, $id)
    {
        Redis::del(RoomTestController::$cacheName);
        $room = RoomTest::query()->findOrFail($id);
        $data = array_merge($request->validated(), ["last_edited" => get_user()->id]);
        $room->update($data);
        return response()->json(['message' => 'Room successfully updated']);
    }

    /**
     * @OA\Delete(
     *     path="/api/tests/{id}",
     *     summary="Delete room",
     *     tags={"RoomTest"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Room successfully deleted"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *      in="path",
     *     description="Id of room",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     * ),
     *     ),
     *  ),
     *
     *
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        Redis::del(RoomTestController::$cacheName);
        RoomTest::query()->findOrFail($id)->delete();
        return response()->json(['message' => 'Room successfully deleted']);
    }

    /**
     * @OA\Post(
     *     path="/api/tests/import",
     *     summary="Import data",
     *     tags={"RoomTest"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Import data",
     * @OA\MediaType(
     *     mediaType="*",
     *     @OA\Schema(
     *     type="file",
     *     format="binary"
     *   ),
     *  ),
     *     ),
     *   @OA\RequestBody(
     *       @OA\MediaType(
     *         mediaType="multipart/form-data",
     *         @OA\Schema(
     *           @OA\Property(
     *             description="excel file",
     *             property="file",
     *             type="string",
     *             format="binary",
     *           ),
     *         )
     *       )
     *    )
     *  ),
     *
     * @param Request $request
     * @return mixed
     */
    public function import(Request $request)
    {
        Redis::del(RoomTestController::$cacheName);
        Redis::del(RoomDetailController::$cacheName);
        Redis::del(ShiftController::$cacheName);
        $file = $request->file('file');
        $time = Carbon::now()->timestamp;
        $file->storeAs('', $time . "_TestImport_" . get_user()->id . ".csv", 's3');
        $file->storeAs('', "data.csv", ['disk' => 'processing']);
        Storage::disk('processing')->put("userid", get_user()->id);
        chdir("/var/www/storage/processing");
        shell_exec("python3 data_process.py > log.txt");
        Storage::disk('s3')->putFileAs("",
            storage_path("processing/log.txt"),
            $time . "_TestImport_" . get_user()->id . ".log");
        Storage::disk('processing')->delete("data.csv");
        Storage::disk('processing')->delete("userid");
        $data = shell_exec("tail -n2 log.txt");
        Storage::disk('processing')->delete("log.txt");
        // get last 2 line of $data
        return $data;
    }

}
