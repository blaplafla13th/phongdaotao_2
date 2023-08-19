<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shift\StartRequest;
use App\Http\Requests\Shift\StoreRequest;
use App\Http\Requests\Shift\UpdateRequest;
use App\Models\Assignment;
use App\Models\RoomTest;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ShiftController extends Controller
{
    public static $cacheName = 'shifts';

    /**
     * @OA\Get(
     * path="/api/shifts",
     * summary="Get list shifts",
     * tags={"Shift"},
     * @OA\Response(
     * response="200",
     * description="List shifts"
     * ),
     * @OA\Parameter(
     * name="name",
     * in="query",
     * description="Name master",
     * required=false,
     * @OA\Schema(
     * type="string"
     * ),
     * ),
     * @OA\Parameter(
     * name="master",
     * in="query",
     * description="master id",
     * required=false,
     * @OA\Schema(
     * type="integer"
     * ),
     * ),
     *          @OA\Parameter(
     *      name="from",
     *      in="query",
     *      description="From date",
     *      required=false,
     *      @OA\Schema(
     *      type="string",
     *      format="date-time"
     *      )
     * ),
     *      @OA\Parameter(
     *      name="to",
     *      in="query",
     *      description="To date",
     *      required=false,
     *      @OA\Schema(
     *      type="string",
     *      format="date-time"
     *      )
     * ),
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Page number",
     * required=false,
     * @OA\Schema(
     * type="integer"
     * ),
     * ),
     * @OA\Parameter(
     * name="size",
     * in="query",
     * description="Number of items per page",
     * required=false,
     * @OA\Schema(
     * type="integer"
     * ),
     * ),
     * @OA\Parameter(
     * name="sortBy",
     * in="query",
     * description="Sort by column",
     * required=false,
     * @OA\Schema(
     * type="string",
     * )
     * ),
     * @OA\Parameter(
     * name="order",
     * in="query",
     * description="Sort order",
     * required=false,
     * @OA\Schema(
     * type="string",
     * )
     * ),
     * ),
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if (!Redis::hexists(ShiftController::$cacheName, json_encode($request->all()))) {
            $shifts = Shift::query();
            if ($request->has('name')) {
                $data = json_decode(e_api()->request('GET', "http://users.blaplafla.test" . '/api/users', [
                    'query' => [
                        'name' => $request->name,
                        'size' => 1000
                    ]
                ])->getBody()->getContents())->data;
                $shifts = $shifts->whereIn('master', array_map(function ($item) {
                    return $item->id;
                }, $data));
            }
            if ($request->has('master')) {
                $shifts = $shifts->where('master', $request->id);
            }
            if ($request->has('from'))
                $shifts = $shifts->where("shift_start_time", ">=", Carbon::parse($request->from)->format('Y/m/d H:i:s'));

            if ($request->has('to'))
                $shifts = $shifts->where("shift_start_time", "<=", Carbon::parse($request->to)->format('Y/m/d H:i:s'));

            if (!$request->has('orderBy') || !in_array($request->orderBy, ['id', 'shift_start_time', "master"])) {
                $request->orderBy = "shift_start_time";
            }
            if (!$request->has('order') || !in_array($request->orderType, ['asc', 'desc'])) {
                $request->orderType = "asc";
            }
            $shifts = $shifts->orderBy($request->orderBy, $request->orderType)
                ->paginate($request->size ?? 10, ['id', 'shift_start_time', "master"],
                    'page', $request->page ?? 0);
            $response = [
                "data" => $shifts->items(),
                "current_page" => $shifts->currentPage(),
                "last_page" => $shifts->lastPage(),
                "per_page" => $shifts->perPage(),
                "total" => $shifts->total()
            ];
            Redis::hset(ShiftController::$cacheName, json_encode($request->all()), json_encode($response));
        }
        return response()->json(json_decode(Redis::hget(ShiftController::$cacheName, json_encode($request->all()))));
    }


    /**
     * @OA\Post(
     *     path="/api/shifts",
     *     summary="Create shift",
     *     tags={"Shift"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Shift successfully created"
     *   ),
     *     @OA\RequestBody(
     *     description="Create shift",
     *     required=true,
     *     @OA\JsonContent(
     *     required={"shift_start_time", "master"},
     *     @OA\Property(
     *     property="shift_start_time",
     *     type="string",
     *      format="date-time",
     *     ),
     *     @OA\Property(
     *     property="master",
     *     type="integer",
     *     ),
     *     ),
     *   )
     * )
     *
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        if ($request->has('master')) {
            $data = e_api()->request('GET', env("USER_MOD", "http://user-api") . '/api/users/' . $request->master);
            if ($data->getStatusCode() != 200) {
                return response()->json(['message' => 'Master not found'], 404);
            }
        }
        Redis::del(ShiftController::$cacheName);
        $data = Shift::query()->create([
            "shift_start_time" => $request->shift_start_time,
            "master" => $request->master,
        ]);
        if ($request->has('master')) {
            e_api()->post(env("USER_MOD", "http://user-api") . '/api/users/send-mail/' . $request->master, [
                'headers' => [
                    'Authorization' => $request->header('Authorization'),
                    'content-type' => "application/json",
                ],
                'body' => json_encode([
                    'subject' => 'Ca thi mới',
                    'body' => 'Mã ca thi của bạn là: ' . $data->url . '. Coi thi lúc: ' . $data->shift_start_time . '.'
                ])
            ]);
        }
        return response()->json(['message' => 'Shift successfully created']);
    }

    /**
     * @OA\Get(
     *     path="/api/shifts/{id}",
     *     summary="Get shift by id",
     *     tags={"Shift"},
     *     @OA\Response(
     *     response="200",
     *     description="Get shift"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *      in="path",
     *     description="Id of shift",
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
        if (!Redis::hexists(ShiftController::$cacheName, $id)) {
            $shift = Shift::query()->findOrFail($id);
            Redis::hset(ShiftController::$cacheName, $id, json_encode($shift));
        }
        return response()->json(json_decode(Redis::hget(ShiftController::$cacheName, $id)));
    }

    /**
     * @OA\Delete(
     *     path="/api/shifts/{id}",
     *     summary="Delete shift",
     *     tags={"Shift"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Shift successfully deleted"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *      in="path",
     *     description="Id of shift",
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
        Redis::del(ShiftController::$cacheName);
        Shift::query()->findOrFail($id)->delete();
        return response()->json(['message' => 'Shift successfully deleted']);
    }

    /**
     * @OA\Post(
     *     path="/api/shifts/start",
     *     summary="Start shift",
     *     tags={"Shift"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Shift successfully started",
     *       @OA\MediaType(
     *      mediaType="image/*",
     *      @OA\Schema(type="string",format="binary")
     *  )
     *    ),
     *
     *     @OA\RequestBody(
     *     description="Start shift",
     *     required=true,
     *     @OA\JsonContent(
     *     required={"url,link_start_time,link_end_time"},
     *     @OA\Property(
     *     property="url",
     *     type="string",
     *     ),
     *     @OA\Property(
     *     property="link_end_time",
     *     type="string",
     *     format="date-time",
     *     ),
     *     ),
     *     ),
     *     ),
     * @param StartRequest $request
     * @return mixed
     */
    public function start(StartRequest $request)
    {
        $shift = Shift::query()->where('url', $request->url)->firstOrFail();
        if (!$shift->is_active) {
            $shift->update([
                'link_start_time' => now(),
                'link_end_time' => $request->link_end_time,
                'is_active' => true,
            ]);
        }
        $rooms = RoomTest::query()->where('shift_id', '=', $shift->id)->get();
        $users = Assignment::query()->where('shift_id', '=', $shift->id)->pluck('supervisor');
        Redis::set("assignment" . $shift->id, json_encode($users));
        Redis::del("supervisor_checkin" . $shift->id);
        $count = 0;
        foreach ($rooms as $room) {
            for ($i = 1; $i <= $room->need_supervisor; $i++) {
                if ($room->getAttribute('supervisor' . $i) == null)
                    Redis::hset("supervisor_checkin" . $shift->id, ++$count, json_encode([
                        'room_test' => $room->id,
                        'position' => $room->room_detail_id,
                        'supervisor' => $i,
                    ]));
            }
        }

//        header('Content-Disposition: attachment;filename="image.png"');
        header('Content-Type: image/png');
        return QrCode::size(200)
            ->format('png')
            ->margin(1)
            ->encoding('UTF-8')
            ->generate("http://" . env('APP_URL') . "api/checkin/join/" . $request->url);
    }

    /**
     * @OA\Put(
     *     path="/api/shifts/{id}",
     *     summary="Update shift",
     *     tags={"Shift"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Shift successfully updated"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *      in="path",
     *     description="Id of shift",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     * ),
     *     ),
     *     @OA\RequestBody(
     *     description="Update shift",
     *     required=true,
     *     @OA\JsonContent(
     *     required={"shift_start_time", "master"},
     *     @OA\Property(
     *     property="shift_start_time",
     *     type="string",
     *      format="date-time",
     *     ),
     *     @OA\Property(
     *     property="master",
     *     type="integer",
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
        if ($request->has('master')) {
            $data = e_api()->request('GET', env("USER_MOD", "http://user-api") . '/api/users/' . $request->master);
            if ($data->getStatusCode() != 200) {
                return response()->json(['message' => 'Master not found'], 404);
            }
        }
        Redis::del(ShiftController::$cacheName);
        $shift = Shift::query()->findOrFail($id);
        $shift->update([
            "shift_start_time" => $request->shift_start_time,
            "master" => $request->master,
        ]);
        if ($request->has('master')) {
            e_api()->post(env("USER_MOD", "http://user-api") . '/api/users/send-mail/' . $request->master, [
                'headers' => [
                    'Authorization' => $request->header('Authorization'),
                    'content-type' => "application/json",
                ],
                'body' => json_encode([
                    'subject' => 'Ca thi mới',
                    'body' => 'Mã ca thi của bạn là: ' . $shift->url . '. Coi thi lúc: ' . $shift->shift_start_time . '.'
                ])
            ]);
        }
        return response()->json(['message' => 'Shift successfully updated']);
    }
}
