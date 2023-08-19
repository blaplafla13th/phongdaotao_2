<?php

namespace App\Http\Controllers;

use App\Http\Requests\Checkin\StoreRequest;
use App\Http\Requests\Checkin\SummaryRequest;
use App\Http\Requests\Checkin\UpdateRequest;
use App\Models\Checkin;
use App\Models\RoomDetail;
use App\Models\RoomTest;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CheckinController extends Controller
{
    public static $cacheName = 'checkins';

    /**
     * @OA\Get(
     * path="/api/checkin",
     * summary="Get list checkin",
     * security={{"bearerAuth":{}}},
     * tags={"Checkin"},
     * @OA\Response(
     * response="200",
     * description="List checkins"
     * ),
     * @OA\Parameter(
     * name="name",
     * in="query",
     * description="User name",
     * required=false,
     * @OA\Schema(
     * type="string"
     * ),
     * ),
     * @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="User Id",
     * required=false,
     * @OA\Schema(
     * type="string"
     * ),
     * ),
     * @OA\Parameter(
     * name="shift_id",
     * in="query",
     * description="shift id",
     * required=false,
     * @OA\Schema(
     * type="integer"
     * ),
     * ),
     *      @OA\Parameter(
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if (!Redis::hexists(CheckinController::$cacheName, json_encode($request->all()))) {
            $checkins = Checkin::query();
            $checkins = $checkins->join('shifts', 'shifts.id', '=', 'checkins.shift_id');

            if ($request->has('name')) {
                $data = json_decode(e_api()->request('GET', env("USER_MOD", "http://user-api") . '/api/users', [
                    'query' => [
                        'name' => $request->name,
                        'size' => 1000
                    ]
                ])->getBody()->getContents())->data;
                $checkins = $checkins->whereIn('supervisor', array_map(function ($item) {
                    return $item->id;
                }, $data));
            }

            if ($request->has('user_id'))
                $checkins = $checkins->where('supervisor', $request->user_id);

            if ($request->has('shift_id'))
                $checkins = $checkins->where('shift_id', $request->shift_id);

            if ($request->has('from'))
                $checkins = $checkins->where("shift_start_time", ">=", Carbon::parse($request->from)->format('Y/m/d H:i:s'));

            if ($request->has('to'))
                $checkins = $checkins->where("shift_start_time", "<=", Carbon::parse($request->to)->format('Y/m/d H:i:s'));

            if (!$request->has('orderBy') || !in_array($request->orderBy, ['id', 'supervisor', "shift_id"]))
                $request->orderBy = "id";

            if (!$request->has('order') || !in_array($request->orderType, ['asc', 'desc']))
                $request->orderType = "desc";

            $checkins = $checkins->orderBy($request->orderBy, $request->orderType)
                ->paginate($request->size ?? 10, [
                    DB::raw('checkins.id as id'),
                    'supervisor',
                    "shift_id",
                    "shift_start_time",
                    'position',
                    "check",],
                    'page', $request->page ?? 0);
            $response = [
                "data" => $checkins->items(),
                "current_page" => $checkins->currentPage(),
                "last_page" => $checkins->lastPage(),
                "per_page" => $checkins->perPage(),
                "total" => $checkins->total()
            ];
            Redis::hset(CheckinController::$cacheName, json_encode($request->all()), json_encode($response));
        }
        return response()->json(json_decode(Redis::hget(CheckinController::$cacheName, json_encode($request->all()))));
    }

    /**
     * @OA\Get (
     *     path="/api/checkin/join/{id}",
     *     summary="Join shift",
     *     tags={"Checkin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Join shift"
     *    ),
     *     @OA\Response(
     *     response="400",
     *     description="Shift is not active"
     *   ),
     *@OA\Response(
     *     response="403",
     *     description="You are not in this shift"
     *  ),
     *     @OA\Response(
     *     response="404",
     *     description="No room to checkin"
     *  ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Shift code",
     *     required=true,
     *     @OA\Schema(
     *     type="string"
     *    ),
     *     ),
     * ),
     *
     * @param $id
     * @return JsonResponse
     */

    public function join($id)
    {
        $shift = Shift::query()->where('url', $id)->firstOrFail();
        if (!$shift->is_active)
            return response()->json(['message' => 'Shift is not active'], 400);
        $users = json_decode(Redis::get("assignment" . $shift->id));
        if (!in_array(get_user()->id, $users))
            return response()->json(['message' => 'You are not in this shift'], 403);
        $data = Checkin::query()
            ->where("supervisor", get_user()->id)
            ->where("shift_id", $shift->id);
        if (!$data->exists()) {
            if (Redis::lLen("supervisor_checkin" . $shift->id) == 0)
                return response()->json(['message' => 'No room to checkin'], 404);
            $room = json_decode(Redis::lpop("supervisor_checkin" . $shift->id));
//            $rooms = Redis::hGetAll("supervisor_checkin" . $shift->id);
//            if (count($rooms) == 0)
//                return response()->json(['message' => 'No room to checkin'], 404);
//            $roomid = array_rand($rooms);
//            $room = json_decode(Redis::hget("supervisor_checkin" . $shift->id, $roomid));
            $position = RoomDetail::query()->where('id', $room->position)->firstOrFail()->name;
            $checkin = Checkin::query()->create([
                'supervisor' => get_user()->id,
                'check' => now(),
                'position' => $position,
                'shift_id' => $shift->id
            ]);
            $test = RoomTest::query()->where('id', $room->room_test)
                ->firstOrFail();
            $test->update([
                'supervisor' . $room->supervisor => $checkin->id,
            ]);
//            Redis::hdel("supervisor_checkin" . $shift->id, $roomid);
            return response()->json([
                'message' => 'Checkin success',
                'position' => $position,
                'exam_test_id' => $test->exam_test_id,
                'supervisor' => $room->supervisor
            ]);
        } else {
            $data = $data->first();
            $test = RoomTest::query()
                ->orWhere('supervisor1', '=', $data->id)
                ->orWhere('supervisor2', '=', $data->id)
                ->orWhere('supervisor3', '=', $data->id)
                ->where('id', '=', $shift->id)
                ->first();
            if ($test->supervisor1 == $data->id)
                $supervisor = 1;
            if ($test->supervisor2 == $data->id)
                $supervisor = 2;
            if ($test->supervisor3 == $data->id)
                $supervisor = 3;
            return response()->json([
                'message' => 'You have already checked in',
                'position' => $data->position,
                'exam_test_id' => $test->exam_test_id,
                'supervisor' => $supervisor
            ]);
        }
    }

    /**
     * @OA\Post(
     *    path="/api/checkin/{id}",
     *   summary="Checkin",
     *  tags={"Checkin"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response="200",
     *     description="Create success"
     *   ),
     * @OA\Response(
     *     response="400",
     *     description="Shift is not active"
     *  ),
     * @OA\RequestBody(
     *     required=true,
     *     description="Create checkin",
     *     request="checkin",
     *     @OA\JsonContent(
     *     required={"supervisor","position"},
     *     @OA\Property(
     *     property="supervisor",
     *     type="integer",
     *     example="1"
     *   ),
     *     @OA\Property(
     *     property="position",
     *     type="string",
     *     example="4T4"
     *  ),
     *     )
     * ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Shift code",
     *     required=true,
     *     @OA\Schema(
     *     type="string"
     *   ),
     *     ),
     *     ),
     *     ),
     *)
     * @param $id
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function create($id, StoreRequest $request)
    {
        $shift = Shift::query()->where('url', $id)->firstOrFail();
        if (!$shift->is_active)
            return response()->json(['message' => 'Shift is not active'], 400);
        Checkin::query()->create([
            'supervisor' => $request->supervisor,
            'shift_id' => $shift->id,
            'position' => $request->position,
            'check' => now(),
        ]);
        return response()->json(['message' => 'Create success']);
    }

    /**
     *
     * @OA\Put(
     *     path="/api/checkin/{id}/{id_checkin}",
     *    summary="Checkin",
     *   tags={"Checkin"},
     *  security={{"bearerAuth":{}}},
     * @OA\Response(
     *      response="200",
     *      description="Update success"
     *    ),
     * @OA\Response(
     *      response="400",
     *      description="Shift is not active"
     *   ),
     * @OA\RequestBody(
     *      required=true,
     *     request="checkin",
     *      description="Create checkin",
     *      @OA\JsonContent(
     *      required={"supervisor","position"},
     *      @OA\Property(
     *      property="supervisor",
     *      type="integer",
     *      example="1"
     *    ),
     *      @OA\Property(
     *      property="position",
     *      type="string",
     *      example="4T4"
     *   ),
     *      @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="Shift code",
     *      required=true,
     *      @OA\Schema(
     *      type="string"
     *    ),
     *      ),
     *     @OA\Parameter(
     *     name="id_checkin",
     *     in="path",
     *     description="Checkin id",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     *  ),
     *      ),
     *      ),
     *     ),
     *     ),
     * @param $id
     * @param UpdateRequest $request
     * @param $id_checkin
     * @return JsonResponse
     */
    public function update($id, UpdateRequest $request, $id_checkin)
    {
        $shift = Shift::query()->where('url', $id)->firstOrFail();
        if (!$shift->is_active)
            return response()->json(['message' => 'Shift is not active'], 400);
        Checkin::query()->findOrFail($id_checkin)->update([
            'supervisor' => $request->supervisor,
            'position' => $request->position,
        ]);
        return response()->json(['message' => 'Update success']);
    }

    /**
     * @OA\Post(
     *     path="/api/checkin/summary",
     *     summary="Summary",
     *     tags={"Checkin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Get summary success"
     *  ),
     *     @OA\RequestBody(
     *     required=true,
     *     request="summary",
     *     description="Get summary",
     *     @OA\JsonContent(
     *     required={""},
     *     @OA\Property(
     *     property="shifts",
     *     type="array",
     *     @OA\Items(
     *     type="integer",
     *     example="1"
     *  ),
     *     ),
     *     @OA\Property(
     *     property="supervisors",
     *     type="array",
     *     @OA\Items(
     *     type="integer",
     *     example="1"
     * ),
     *),
     *     @OA\Property(
     *     property="from",
     *     type="string",
     *     format="date"
     *     ),
     *     @OA\Property(
     *     property="to",
     *     type="string",
     *     format="date"
     *     ),
     *     ),
     *)
     * )
     * @param SummaryRequest $request
     * @return JsonResponse
     */
    public function summary(SummaryRequest $request)
    {
        $checkin = Checkin::query()->select(
            DB::raw("checkins.supervisor as supervisor"),
            DB::raw('count(*) as total'),
            DB::raw('count(*) filter ( where checkins.check > shifts.link_end_time ) as late')
        )->join('shifts', 'shifts.id', '=', 'checkins.shift_id')
            ->groupBy("checkins.supervisor");

        if ($request->exists('from') || $request->exists('to')) {
            if ($request->exists('from') && $request->exists('to')) {
                $from = Carbon::parse($request->from)->format('Y/m/d 00:00:00');
                $to = Carbon::parse($request->to)->format('Y/m/d 23:59:59');
            } else {
                $time = $request->from ?? $request->to;
                $from = Carbon::parse($time)->format('Y/m/d 00:00:00');
                $to = Carbon::parse($time)->format('Y/m/d 23:59:59');
            }
            $checkin = $checkin->whereBetween('shifts.shift_start_time', [$from, $to]);
        }

        if ($request->exists('shifts')) {
            $checkin = $checkin->whereIn('shifts.id', $request->shifts);
        }

        if ($request->has('supervisors')) {
            $checkin = $checkin->whereIn('supervisor', $request->supervisors);
        }

//        DB::enableQueryLog();
        $checkin = $checkin->get();
//        return DB::getQueryLog();
        if (!isset($from) || !isset($to)) {
            if ($request->exists('shifts')) {
                $from = Shift::query()->whereIn('id', $request->shifts)->min('shift_start_time');
                $to = Shift::query()->whereIn('id', $request->shifts)->max('shift_start_time');
            } else {
                $from = Shift::query()->min('shift_start_time');
                $to = Shift::query()->max('shift_start_time');
            }
        }
        return response()->json([
            'message' => 'Get summary success',
            'from' => $from,
            'to' => $to,
            'data' => $checkin
        ]);
    }
}
