<?php

namespace App\Http\Controllers;

use App\Http\Requests\Room\StoreRequest;
use App\Http\Requests\Room\UpdateRequest;
use App\Models\RoomDetail;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RoomDetailController extends Controller
{
    public static $cacheName = 'room_details';

    /**
     * @OA\Get(
     * path="/api/rooms",
     * summary="Get list rooms",
     * tags={"RoomDetail"},
     * @OA\Response(
     * response="200",
     * description="List rooms"
     * ),
     * @OA\Parameter(
     * name="name",
     * in="query",
     * description="Name of room",
     * required=false,
     * @OA\Schema(
     * type="string"
     * ),
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
        if (!Redis::hexists(RoomDetailController::$cacheName, json_encode($request->all()))) {
            $rooms = RoomDetail::query();
            if ($request->has('name'))
                $rooms = $rooms->where("name", "ILIKE", "%" . $request->name . "%");
            if (!$request->has('orderBy') || !in_array($request->orderBy, ['id', 'name'])) {
                $request->orderBy = "id";
            }
            if (!$request->has('order') || !in_array($request->orderType, ['asc', 'desc'])) {
                $request->orderType = "desc";
            }
            $rooms = $rooms->orderBy($request->orderBy, $request->orderType)
                ->paginate($request->size ?? 10, ['id', 'name'],
                    'page', $request->page ?? 0);
            $response = [
                "data" => $rooms->items(),
                "current_page" => $rooms->currentPage(),
                "last_page" => $rooms->lastPage(),
                "per_page" => $rooms->perPage(),
                "total" => $rooms->total()
            ];
            Redis::hset(RoomDetailController::$cacheName, json_encode($request->all()), json_encode($response));
        }
        return response()->json(json_decode(Redis::hget(RoomDetailController::$cacheName, json_encode($request->all()))));
    }


    /**
     * @OA\Post(
     *     path="/api/rooms",
     *     summary="Create room",
     *     tags={"RoomDetail"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Room successfully created"
     *   ),
     *     @OA\RequestBody(
     *     description="Create room",
     *     required=true,
     *     @OA\JsonContent(
     *     required={"name"},
     *     @OA\Property(
     *     property="name",
     *     type="string",
     *     ),
     *     ),
     *     ),
     *   )
     *
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        Redis::del(RoomDetailController::$cacheName);
        RoomDetail::query()->create($request->validated());
        return response()->json(['message' => 'Room successfully created']);
    }

    /**
     * @OA\Get(
     *     path="/api/rooms/{id}",
     *     summary="Get room by id",
     *     tags={"RoomDetail"},
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
        if (!Redis::hexists(RoomDetailController::$cacheName, $id)) {
            $room = RoomDetail::query()->findOrFail($id);
            Redis::hset(RoomDetailController::$cacheName, $id, json_encode($room));
        }
        return response()->json(json_decode(Redis::hget(RoomDetailController::$cacheName, $id)));
    }


    /**
     * @OA\Put(
     *     path="/api/rooms/{id}",
     *     summary="Update room",
     *     tags={"RoomDetail"},
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
     *     @OA\JsonContent(
     *     required={"name"},
     *     @OA\Property(
     *     property="name",
     *     type="string",
     *     example="Room 1"
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
        Redis::del(RoomDetailController::$cacheName);
        $room = RoomDetail::query()->findOrFail($id);
        $room->update($request->validated());
        return response()->json(['message' => 'Room successfully updated']);
    }

    /**
     * @OA\Delete(
     *     path="/api/rooms/{id}",
     *     summary="Delete room",
     *     tags={"RoomDetail"},
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
        Redis::del(RoomDetailController::$cacheName);
        RoomDetail::query()->findOrFail($id)->delete();
        return response()->json(['message' => 'Room successfully deleted']);
    }

    /**
     * @OA\Get(
     * path="/api/rooms/template",
     * summary="Get template",
     * tags={"RoomDetail"},
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
        $header = [array("STT", "Tên phòng")];
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
     *     path="/api/rooms/template",
     *     summary="Import rooms",
     *     tags={"RoomDetail"},
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
     *   )
     * ),
     *
     * @param Request $request
     * @return JsonResponse
     */


    public function import(Request $request): JsonResponse
    {
        Redis::del(RoomDetailController::$cacheName);
        $file = $request->file('file');
        $file->storeAs('', Carbon::now()->timestamp . "_RoomImport_" . auth()->id() . ".xlsx", 's3');
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        try {
            $spreadsheet = $reader->load($file);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error when reading file'], 500);
        }
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        $log = [];
        for ($i = 1; $i < count($sheetData); $i++) {
            $room = RoomDetail::query();
            if ($sheetData[$i][1] == null ||
                RoomDetail::query()->where('name', $sheetData[$i][1])->first() != null) {
                $log[] = $sheetData[$i][1];
                continue;
            }
            $room->create([
                'name' => $sheetData[$i][1]
            ]);
        }
        return response()->json(['message' => 'Import successfully'
            , 'log' => $log]);
    }
}
