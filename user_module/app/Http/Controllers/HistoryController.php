<?php

namespace App\Http\Controllers;

use App\Enums\ActionType;
use App\Models\Department;
use App\Models\DepartmentHistory;
use App\Models\Notify;
use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    // User
    public function showHistoryUser(Request $request): JsonResponse
    {
        $history=UserHistory::query();
        if ($request->has('id'))
            $history = $history->where('user_id', $request->id);
        return response()->json($history->paginate($request->size??10, ['*'], 'page', $request->page??0));
    }

    public function detailHistoryUser($id): JsonResponse
    {
        $history = UserHistory::query()->findOrFail($id);
        return response()->json($history);
    }

    public function restoreUser($id): JsonResponse
    {
        $history = UserHistory::query()->findOrFail($id);
        if ($history->status != ActionType::CREATE)
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
        else if ($history->user_id != auth()->id())
            User::query()->findOrFail($history->user_id)->delete();
        else
            return response()->json(['message' => 'You cannot delete your own account'], 403);
        return response()->json(['message' => 'Restore successfully']);
    }

    // Department
    public function showHistoryDepartment(Request $request): JsonResponse
    {
        $history = DepartmentHistory::query();
        if ($request->has('department_id')){
            $history->where('department_id', $request->department_id);
        }
        return response()->json($history->paginate($request->size??10, ['*'],
            'page', $request->page??0));
    }

    public function detailHistoryDepartment($id): JsonResponse
    {
        $history = DepartmentHistory::query()->findOrFail($id);
        return response()->json($history);
    }

    public function restoreDepartment($id): JsonResponse
    {
        $history = DepartmentHistory::query()->findOrFail($id);
        if ($history->status != ActionType::CREATE)
            Department::query()->createOrUpdate([
                'id' => $history->department_id
            ], [
                'name' => $history->name
            ]);
        else
            Department::query()->findOrFail($history->department_id)->delete();
        return response()->json(['message' => 'Restore successfully']);
    }

    //Notification
    public function showHistoryNotification(Request $request): JsonResponse
    {
        $history=Notify::query();
        if ($request->has('id'))
            $history = $history->where('to', $request->id);
        return response()->json($history->paginate($request->size??10, ['*'], 'page', $request->page??0));
    }
}
