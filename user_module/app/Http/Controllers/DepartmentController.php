<?php

namespace App\Http\Controllers;

use App\Enums\ActionType;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\DepartmentHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $departments = Department::query();
        if ($request->has('name'))
            $departments = $departments->whereRaw(
                'LOWER(`name`) like ?',
                "%".strtolower($request->name)."%"
            );

        return response()->json($departments->paginate($request->size??10, ['id','name'],
                'page', $request->page??0)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreDepartmentRequest $request
     * @return JsonResponse
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        Department::query()->create($request->validated());
        return response()->json(['message' => 'Department successfully created']);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $department = Department::query()->findOrFail($id);
        return response()->json($department);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateDepartmentRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function update(UpdateDepartmentRequest $request, $id): JsonResponse
    {
        $department = Department::query()->findOrFail($id);
        $department->update($request->validated());
        return response()->json(['message' => 'Department successfully updated']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        Department::query()->findOrFail($id)->delete();
        return response()->json(['message' => 'Department successfully deleted']);
    }

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
        try {
            $writer->save('php://output');
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
            return response()->json(['message' => 'Error when downloading file'], 500);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $file = $request->file('file');
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
