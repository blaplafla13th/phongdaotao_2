<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assignment\StoreRequest;
use App\Http\Requests\Assignment\UpdateRequest;
use App\Models\Assignment;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AssignmentController extends Controller
{
    public static $cacheName = 'assignments';

    /**
     * @OA\Get(
     * path="/api/assignments",
     * summary="Get list assignments",
     * tags={"Assignment"},
     * @OA\Response(
     * response="200",
     * description="List assignments"
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
        if (!Redis::hexists(AssignmentController::$cacheName, json_encode($request->all()))) {
            $assignments = Assignment::query();
            $assignments = $assignments->join('shifts', 'shifts.id', '=', 'assignments.shift_id');

            if ($request->has('name')) {
                $data = json_decode(e_api()->request('GET', "http://users.blaplafla.test" . '/api/users', [
                    'query' => [
                        'name' => $request->name,
                        'size' => 1000
                    ]
                ])->getBody()->getContents())->data;
                $assignments = $assignments->whereIn('supervisor', array_map(function ($item) {
                    return $item->id;
                }, $data));
            }

            if ($request->has('user_id'))
                $assignments = $assignments->where('supervisor', $request->id);

            if ($request->has('shift_id'))
                $assignments = $assignments->where('shift_id', $request->id);

            if ($request->has('from'))
                $assignments = $assignments->where("shift_start_time", ">=", Carbon::parse($request->from)->format('Y/m/d H:i:s'));

            if ($request->has('to'))
                $assignments = $assignments->where("shift_start_time", "<=", Carbon::parse($request->to)->format('Y/m/d H:i:s'));

            if (!$request->has('orderBy') || !in_array($request->orderBy, ['id', 'supervisor', "shift_id"]))
                $request->orderBy = "id";

            if (!$request->has('order') || !in_array($request->orderType, ['asc', 'desc']))
                $request->orderType = "desc";

            $assignments = $assignments->orderBy($request->orderBy, $request->orderType)
                ->paginate($request->size ?? 10, [DB::raw('assignments.id as id'), 'supervisor', "shift_id", "shift_start_time"],
                    'page', $request->page ?? 0);
            $response = [
                "data" => $assignments->items(),
                "current_page" => $assignments->currentPage(),
                "last_page" => $assignments->lastPage(),
                "per_page" => $assignments->perPage(),
                "total" => $assignments->total()
            ];
            Redis::hset(AssignmentController::$cacheName, json_encode($request->all()), json_encode($response));
        }
        return response()->json(json_decode(Redis::hget(AssignmentController::$cacheName, json_encode($request->all()))));
    }


    /**
     * @OA\Post(
     *     path="/api/assignments",
     *     summary="Create assignment",
     *     tags={"Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Assignment successfully created"
     *   ),
     *     @OA\RequestBody(
     *     description="Create assignment",
     *     required=true,
     *     @OA\JsonContent(
     *     required={"supervisor", "shift_id"},
     *     @OA\Property(
     *     property="supervisor",
     *     type="integer",
     *     ),
     *     @OA\Property(
     *     property="shift_id",
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
        $data = e_api()->request('GET', "http://users.blaplafla.test" . '/api/users/' . $request->supervisor);
        if ($data->getStatusCode() != 200) {
            return response()->json(['message' => 'Supervisor not found'], 404);
        }
        Redis::del(AssignmentController::$cacheName);
        Assignment::query()->create($request->validated());
        return response()->json(['message' => 'Assignment successfully created']);
    }

    /**
     * @OA\Get(
     *     path="/api/assignments/{id}",
     *     summary="Get assignment by id",
     *     tags={"Assignment"},
     *     @OA\Response(
     *     response="200",
     *     description="Get assignment"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *      in="path",
     *     description="Id of assignment",
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
        if (!Redis::hexists(AssignmentController::$cacheName, $id)) {
            $assignment = Assignment::query()->findOrFail($id);
            Redis::hset(AssignmentController::$cacheName, $id, json_encode($assignment));
        }
        return response()->json(json_decode(Redis::hget(AssignmentController::$cacheName, $id)));
    }


    /**
     * @OA\Put(
     *     path="/api/assignments/{id}",
     *     summary="Update assignment",
     *     tags={"Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Assignment successfully updated"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *      in="path",
     *     description="Id of assignment",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     * ),
     *     ),
     *     @OA\RequestBody(
     *     description="Update assignment",
     *     required=true,
     *     @OA\JsonContent(
     *     required={"supervisor", "shift_id"},
     *     @OA\Property(
     *     property="supervisor",
     *     type="integer",
     *     ),
     *     @OA\Property(
     *     property="shift_id",
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
        $data = e_api()->request('GET', "http://users.blaplafla.test" . '/api/users/' . $request->supervisor);
        if ($data->getStatusCode() != 200) {
            return response()->json(['message' => 'Supervisor not found'], 404);
        }
        Redis::del(AssignmentController::$cacheName);
        $assignment = Assignment::query()->findOrFail($id);
        $assignment->update($request->validated());
        return response()->json(['message' => 'Assignment successfully updated']);
    }

    /**
     * @OA\Delete(
     *     path="/api/assignments/{id}",
     *     summary="Delete assignment",
     *     tags={"Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Assignment successfully deleted"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *      in="path",
     *     description="Id of assignment",
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
        Redis::del(AssignmentController::$cacheName);
        Assignment::query()->findOrFail($id)->delete();
        return response()->json(['message' => 'Assignment successfully deleted']);
    }
    /**
     * @OA\Get(
     * path="/api/assignments/template",
     * summary="Get template",
     * tags={"Assignment"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response="200",
     * description="Template file",
     *  @OA\Schema(type="file")
     * ),
     *     ),
     * /
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getTemplate()
    {
        $header = [array("STT", "Tên giám thị", "Số điện thoại")];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($header, null, 'A1');
        $writer = new Xlsx($spreadsheet);
        // redirect output to client browser
        header('Content-Disposition: attachment;filename="template.xlsx"');
        header('Cache-Control: max-age=0');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $writer->save('php://output');
    }

    /**
     * @OA\Post(
     *     path="/api/assignments/template/{id}",
     *     summary="Import assignments",
     *     tags={"Assignment"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *          response="200",
     *          description="Import successfully"
     *      ),
     *     @OA\RequestBody(
     *      @OA\MediaType(
     *        mediaType="multipart/form-data",
     *        @OA\Schema(
     *          @OA\Property(
     *            description="excel file",
     *            property="file",
     *            type="string",
     *            format="binary",
     *          ),
     *        )
     *      )
     *   ),
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id of shift",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     * ),
     * ),
     *)
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request, $id)
    {
        $shift = Shift::query()->firstOrFail($id)->get();
        Redis::del(AssignmentController::$cacheName);
        $file = $request->file('file');
        $file->storeAs('', Carbon::now()->timestamp . "_AssignmentImport_" . auth()->id() . ".xlsx", 's3');
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        try {
            $spreadsheet = $reader->load($file);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error when reading file'], 500);
        }
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        $log = [];
        for ($i = 1; $i < count($sheetData); $i++) {
            $data = json_decode(e_api()->get(env("USER_MOD", "http://user-api") . '/api/users', [
                'query' => [
                    'name' => $sheetData[$i][1],
                    'phone' => $sheetData[$i][2]
                ]
            ])->getBody()->getContents());
            if ($data->total != 1)
                $log[] = "Line " . ($i + 1) . ": " . "Supervisor not found: name="
                    . $sheetData[$i][1] . ", phone=" . $sheetData[$i][2];

            $assignment = Assignment::query()->create([
                'supervisor' => $data->data[0]->id,
                'shift_id' => $id
            ]);
            e_api()->post(env("USER_MOD", "http://user-api") . '/api/users/send-mail/' . $request->master, [
                'headers' => ['Authorization' => $request->header('Authorization')],
                'body' => json_encode([
                    'subject' => 'Phân công coi thi',
                    'body' => 'Bạn được phân công coi thi lúc ' . $shift->shift_start_time,
                ])
            ]);
        }
        return response()->json(['message' => 'Import successfully'
            , 'log' => $log]);
    }
}
