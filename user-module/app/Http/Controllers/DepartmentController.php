<?php

namespace App\Http\Controllers;

use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DepartmentController extends Controller
{

    public static $cacheName = 'departments';
    /**
     * @OA\Get(
     *     path="/api/departments",
     *     summary="Get list departments",
     *     tags={"Department"},
     *       @OA\Response(
     *           response="200",
     *           description="List departments"
     *       ),
     *     @OA\Parameter(
     *     name="name",
     *     in="query",
     *     description="Name of department",
     *     required=false,
     *     @OA\Schema(
     *     type="string"
     *    ),
     *     ),
     *     @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Page number",
     *     required=false,
     *     @OA\Schema(
     *     type="integer"
     *   ),
     *     ),
     *     @OA\Parameter(
     *     name="size",
     *     in="query",
     *     description="Number of items per page",
     *     required=false,
     *     @OA\Schema(
     *     type="integer"
     *   ),
     *     ),
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
     *  ),
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if (!Redis::hexists(DepartmentController::$cacheName, json_encode($request->all()))) {
            $departments = Department::query();
            if ($request->has('name'))
                $departments = $departments->whereRaw(
                    'LOWER(`name`) like ?',
                    "%" . strtolower($request->name) . "%"
                );
            if (!$request->has('orderBy') || !in_array($request->orderBy, ['id', 'name', "created_at"])) {
                $request->orderBy = "created_at";
            }
            if (!$request->has('order') || !in_array($request->orderType, ['asc', 'desc'])) {
                $request->orderType = "desc";
            }
            $departments = $departments->orderBy($request->orderBy, $request->orderType)
                ->paginate($request->size ?? 10, ['id', 'name', "created_at"],
                'page', $request->page ?? 0);
            Redis::hset(DepartmentController::$cacheName, json_encode($request->all()), json_encode($departments));
        }
        return response()->json(json_decode(Redis::hget(DepartmentController::$cacheName, json_encode($request->all()))));
    }

    /**
     * @OA\Post(
     *     path="/api/departments",
     *     summary="Create new department",
     *     tags={"Department"},
     *     security={{"bearerAuth":{}}},
     *       @OA\Response(
     *          response="200",
     *          description="Department successfully created"
     *      ),
     *     @OA\RequestBody(
     *     required=true,
     *     description="Create new department",
     *     @OA\JsonContent(
     *     required={"name"},
     *     @OA\Property(property="name", type="string", example="Department 1"),
     *     ),
     *     ),
     *),
     * Store a newly created resource in storage.
     *
     * @param StoreDepartmentRequest $request
     * @return JsonResponse
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        Redis::del(DepartmentController::$cacheName);
        Department::query()->create($request->validated());
        return response()->json(['message' => 'Department successfully created']);
    }

    /**
     *
     * @OA\Get(
     *     path="/api/departments/{id}",
     *     summary="Get department by id",
     *     tags={"Department"},
     *      @OA\Response(
     *          response="200",
     *          description="Department"
     *     ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id of department",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     *   ),
     *   ),
     *     ),
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        if (!Redis::hexists(DepartmentController::$cacheName, $id)) {
            $department = Department::query()->findOrFail($id);
            Redis::hset(DepartmentController::$cacheName, $id, json_encode($department));
        }
        return response()->json(json_decode(Redis::hget(DepartmentController::$cacheName, $id)));
    }

    /**
     * @OA\Put(
     *     path="/api/departments/{id}",
     *     summary="Update department",
     *     tags={"Department"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Department successfully updated"
     *    ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id of department",
     *     required=true,
     *     @OA\Schema(
     *     type="integer"
     *  ),
     *     ),
     *     @OA\RequestBody(
     *     required=true,
     *     description="Update department",
     *     @OA\JsonContent(
     *     required={"name"},
     *     @OA\Property(property="name", type="string", example="Department 1"),
     *     ),
     *     ),
     *
     *),
     *
     * Update the specified resource in storage.
     *
     * @param UpdateDepartmentRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function update(UpdateDepartmentRequest $request, $id): JsonResponse
    {
        Redis::del(DepartmentController::$cacheName);
        $department = Department::query()->findOrFail($id);
        $department->update($request->validated());
        return response()->json(['message' => 'Department successfully updated']);
    }

    /**
     * @OA\Delete(
     *     path="/api/departments/{id}",
     *     summary="Delete department",
     *     tags={"Department"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Department successfully deleted"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *      in="path",
     *     description="Id of department",
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
    public function destroy($id): JsonResponse
    {
        Redis::del(DepartmentController::$cacheName);
        Department::query()->findOrFail($id)->delete();
        return response()->json(['message' => 'Department successfully deleted']);
    }


    /**
     * @OA\Get(
     * path="/api/template/department",
     * summary="Get template",
     * tags={"Department"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response="200",
     * description="Template file",
     *  @OA\Schema(type="file")
     * ),
     *     ),
     * /
     * @return JsonResponse|void
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getTemplate()
    {
        $header = [array("STT","Tên khoa")];
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
     *     path="/api/template/department",
     *     summary="Import departments",
     *     tags={"Department"},
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
        Redis::del(DepartmentController::$cacheName);
        $file = $request->file('file');
        $file->storeAs('', Carbon::now()->timestamp . "_DepartmentImport_" . auth()->id() . ".xlsx", 's3');
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        try {
            $spreadsheet = $reader->load($file);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error when reading file'], 500);
        }
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        $log= [];
        for ($i = 1; $i < count($sheetData); $i++){
            $department = Department::query();
            if ($sheetData[$i][1] == null ||
                Department::query()->where('name', $sheetData[$i][1])->first() != null){
                $log[] = $sheetData[$i][1];
                continue;
            }
            $department->create([
                'name' => $sheetData[$i][1]
            ]);
        }
        return response()->json(['message' => 'Import successfully'
            , 'log' => $log]);
    }
}
