<?php
namespace App\Http\Controllers;
use App\Enums\NotiType;
use App\Enums\UserType;
use App\Http\Requests\Auth\CreateAccountRequest;
use App\Http\Requests\Auth\UpdateAccountRequest;
use App\Http\Requests\User\MailRequest;
use App\Http\Requests\User\SmsRequest;
use App\Models\Department;
use App\Models\Notify;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller{

    public static $cacheName = 'users';
    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create new user",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="User successfully created"
     *    ),
     *     @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *     required={"name","email","password","phone","role","department_id"},
     *     @OA\Property(property="name", type="string", example="John Doe"),
     *     @OA\Property(property="email", type="string", example="example@example.com"),
     *     @OA\Property(property="password", type="string", example="Password@123"),
     *     @OA\Property(property="phone", type="string", example="0123456789"),
     *     @OA\Property(property="role", type="string", example="1"),
     *     @OA\Property(property="department_id", type="string", example="1"),
     *     ),
     *     ),
     * ),
     *
     * @param CreateAccountRequest $request
     * @return JsonResponse
     */
    protected function createAccount(CreateAccountRequest $request): JsonResponse
    {
        Redis::del(UserController::$cacheName);
        Redis::del(HistoryController::$cacheNameUsers);
        (new User())->query()->create($request->validated());
        return response()->json(['message' => 'User successfully created']);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update user",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="User successfully updated"
     *   ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="User id",
     *     required=true,
     *     @OA\Schema(
     *     type="integer",
     *      ),
     *     ),
     *     @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *     required={"name","email","password","phone","role","department_id"},
     *     @OA\Property(property="name", type="string", example="John Doe"),
     *     @OA\Property(property="email", type="string", example="example@example.com"),
     *     @OA\Property(property="password", type="string", example="Password@123"),
     *     @OA\Property(property="phone", type="string", example="0123456789"),
     *     @OA\Property(property="role", type="string", example="1"),
     *     @OA\Property(property="department_id", type="string", example="1"),
     *     ),
     *     ),
     * ),
     * @param UpdateAccountRequest $request
     * @param $id
     * @return JsonResponse
     */
    protected function updateAccount(UpdateAccountRequest $request, $id)/*: JsonResponse*/
    {
        Redis::del(UserController::$cacheName);
        Redis::del(HistoryController::$cacheNameUsers);
        $this->revokeToken($id);
        $user = User::query()->findOrFail($id);
        if ($request->password !== null || $request->password !== '')
            $request->merge(['password' => bcrypt($request->password)]);
        $user->update($request->validated());
        return response()->json(['message' => 'User successfully updated']);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Delete user",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="User successfully deleted"
     *  ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="User id",
     *     required=true,
     *     @OA\Schema(
     *     type="integer",
     *     )
     *    ),
     * ),
     * @param $id
     * @return JsonResponse
     */
    protected function deleteAccount($id): JsonResponse
    {
        Redis::del(UserController::$cacheName);
        Redis::del(HistoryController::$cacheNameUsers);
        $this->revokeToken($id);
        $user = User::query()->findOrFail($id);
        if (auth()->id() === $user->id)
            return response()->json(['message' => 'You cannot delete your own account'], 403);
        $user->delete();
        return response()->json(['message' => 'User successfully deleted']);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get user",
     *     tags={"User"},
     *     @OA\Response(
     *     response="200",
     *     description="User successfully retrieved"
     * ),
     *     @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="User id",
     *     required=true,
     *     @OA\Schema(
     *     type="integer",
     *     )
     *   ),
     * ),
     * @param $id
     * @return JsonResponse
     */
    protected function getAccount($id): JsonResponse
    {
        if (!Redis::hexists(UserController::$cacheName, $id)) {
            $user = User::query()->findOrFail($id);
            Redis::hset(UserController::$cacheName, $id, json_encode($user));
        }
        return response()->json(json_decode(Redis::hget(UserController::$cacheName, $id)));
    }


    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Get users",
     *     tags={"User"},
     *     @OA\Response(
     *     response="200",
     *     description="Users successfully retrieved"
     * ),
     *     @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Page number",
     *     required=false,
     *     @OA\Schema(
     *     type="integer",
     *     )
     *  ),
     *     @OA\Parameter(
     *     name="size",
     *     in="query",
     *     description="Page size",
     *     required=false,
     *     @OA\Schema(
     *     type="integer",
     *     )
     *     ),
     *     @OA\Parameter(
     *     name="name",
     *     in="query",
     *     description="User name",
     *     required=false,
     *     @OA\Schema(
     *     type="string",
     *     )
     *    ),
     *     @OA\Parameter(
     *     name="role",
     *      in="query",
     *     description="User role",
     *     required=false,
     *     @OA\Schema(
     *     type="integer",
     *     )
     *   ),
     *     @OA\Parameter(
     *     name="email",
     *     in="query",
     *     description="User email",
     *     required=false,
     *     @OA\Schema(
     *     type="string",
     *     )
     *  ),
     *     @OA\Parameter(
     *     name="phone",
     *     in="query",
     *     description="User phone",
     *     required=false,
     *     @OA\Schema(
     *     type="string",
     *     )
     *  ),
     *    @OA\Parameter(
     *     name="department_id",
     *     in="query",
     *     description="User department id",
     *     required=false,
     *     @OA\Schema(
     *     type="integer",
     *     )
     *  ),
     *     @OA\Parameter(
     *     name="sortBy",
     *     in="query",
     *     description="Sort by column",
     *     required=false,
     *     @OA\Schema(
     *     type="string",
     *     )
     *     ),
     *     @OA\Parameter(
     *     name="order",
     *     in="query",
     *     description="Sort order",
     *      required=false,
     *     @OA\Schema(
     *     type="string",
     *     )
     *    ),
     * ),
     * @param Request $request
     * @return JsonResponse
     */
    protected function searchAccount(Request $request): JsonResponse
    {
        if (!Redis::hexists(UserController::$cacheName, json_encode($request->all()))) {
        $users = User::query();
        if ($request->has('name')){
            $users = $users->whereRaw('LOWER(`name`) like ?', "%".strtolower($request->name)."%");
        }
        if ($request->has('role'))
            $users = $users->where('role', $request->role);
        if ($request->has('email'))
            $users = $users->where('email', 'like', '%'.$request->email.'%');
        if ($request->has('phone'))
            $users = $users->where('phone', 'like', '%'.$request->phone.'%');
        if ($request->has('department_id'))
            $users = $users->where('department_id', $request->department_id);
            if (!$request->has('sortBy') || !in_array($request->sortBy, ['id', 'name', 'email', 'phone',
                    'role', 'department_id', 'created_at']))
                $request->sortBy = 'created_at';
            if (!$request->has('order') || !in_array($request->order, ['asc', 'desc']))
                $request->order = 'desc';
            $users = $users->orderBy($request->sortBy, $request->order)
                ->paginate($request->size ?? 10, ['id', 'name', 'email', 'phone', 'role', 'department_id', 'created_at'], 'page', $request->page ?? 0);
            Redis::hset(UserController::$cacheName, json_encode($request->all()), json_encode($users));
        }
        return response()->json(json_decode(Redis::hget(UserController::$cacheName, json_encode($request->all()))));
    }

    /**
     * @OA\Get(
     *     path="/api/template/account",
     *     summary="Get template",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *   @OA\Response(
     *  response="200",
     *  description="Template file",
     *   @OA\Schema(
     *       type="file",
     *       format="binary"
     *      ),
     *  ),
     * ),
     *
     * @return void
     * @throws Exception
     */

    protected function excelTemplate()
    {
        // $header is an array containing column headers
        $header = [array("STT","Họ và tên","Email","Số điện thoại","Vai trò","Khoa")];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($header);

        // redirect output to client browser
        header('Content-Disposition: attachment;filename="template.xlsx"');
        header('Cache-Control: max-age=0');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

    }

    /**
     * @OA\Post(
     *     path="/api/template/account",
     *     summary="Import users",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Users successfully imported"
     * ),
     *          @OA\RequestBody(
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
     * ),
     * @param Request $request
     * @return JsonResponse
     */
    protected function importAccount(Request $request): JsonResponse
    {
        Redis::del(UserController::$cacheName);
        $file = $request->file('file');
        $file->storeAs('', Carbon::now()->timestamp . "_UserImport_" . auth()->id() . ".xlsx", 's3');
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        try {
            $spreadsheet = $reader->load($file);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception) {
            return response()->json(['message' => 'Error when reading file'], 500);
        }
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        $log=[];
        for ($i = 1; $i < count($sheetData); $i++){
            $user = User::query();
            if ($user->where('email', $sheetData[$i][2])->exists()){
                $log[] = "Trùng email tại dòng $i: {$sheetData[$i][2]}";
                continue;
            }
            if ($user->where('phone', $sheetData[$i][3])->exists()){
                $log[] = "Trùng số điện thoại tại dòng $i: {$sheetData[$i][3]}";
                continue;
            }
            $sheetData[$i][3] = str_replace([' ','-','(',')',"'"],'',$sheetData[$i][3]);
            $sheetData[$i][3] = str_replace(["+84"],'0',$sheetData[$i][3]);
            if (strlen($sheetData[$i][3]) != 10){
                $log[] = "Số điện thoại không hợp lệ tại dòng $i: {$sheetData[$i][3]}";
                continue;
            }
            if (filter_var($sheetData[$i][2], FILTER_VALIDATE_EMAIL) === false){
                $log[] = "Email không hợp lệ tại dòng $i: {$sheetData[$i][2]}";
                continue;
            }
            if (!in_array($sheetData[$i][4], UserType::available())){
                $log[] = "Vai trò không hợp lệ tại dòng $i: {$sheetData[$i][4]}";
                continue;
            }
            if ($sheetData[$i][5] != null && $department=Department::query()->where('name', $sheetData[$i][5])->first() === null){
                $log[] = "Khoa không tồn tại tại dòng $i: {$sheetData[$i][5]}";
                continue;
            }
           $password = Hash::make(Carbon::now()->timestamp.$sheetData[$i][2].$sheetData[$i][3].Str::random(10));
            $temp=$user->create([
                "name" => $sheetData[$i][1],
                "email" => $sheetData[$i][2],
                "phone" => $sheetData[$i][3],
                "role" => $sheetData[$i][4],
                "password" => bcrypt($password),
                "department_id" => $department->id??null,
            ]);
            Mail::raw("Tài khoản của bạn là:".$sheetData[$i][2]."\n".
            "Mật khẩu của bạn là: ".$password
                , function ($message) use ($request, $temp) {
                $message->to($temp->email, $temp->name)
                    ->subject("Mật khẩu đăng nhập hệ thống HUS");
            });
            Notify::query()->create([
                'from' => auth()->id(),
                'to' => $temp->id,
                'address' => $user->email,
                'content' => "Mật khẩu đăng nhập hệ thống HUS",
                'type' => NotiType::Email,
            ]);
        }
        return response()->json([
            'message' => 'Import successfully',
            'log' => $log
            ]);
    }

    /**
     * @OA\Post(
     *    path="/api/users/send-mail/{id}",
     *     summary="Send mail to user",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Send mail successfully"
     * ),
     *     @OA\RequestBody(
     *     required=true,
     *      @OA\JsonContent(
     *         required={"subject","body"},
     *      @OA\Property(property="subject", type="string", example="Subject"),
     *      @OA\Property(property="body", type="string", example="Body"),
     *     )
     * ),
     *          @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="User id",
     *      required=true,
     *      @OA\Schema(
     *      type="integer",
     *      )
     *     ),
     * ),
     *
     * @param $id
     * @param MailRequest $request
     * @return JsonResponse
     */
    public function mail($id, MailRequest $request): JsonResponse
    {
        Redis::del(HistoryController::$cacheNameNoti);
        $user = User::query()->findOrFail($id);
        Mail::raw($request->body, function ($message) use ($request, $user) {
            $message->to($user->email, $user->name)
                ->subject($request->subject);
        });
        Notify::query()->create([
            'from' => auth()->id(),
            'to' => $user->id,
            'content' => $request->subject.": ".$request->body,
            'address' => $user->email,
            'kind' => NotiType::Email,
        ]);
        return response()->json(['message' => 'Send mail successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/users/send-sms/{id}",
     *     summary="Send sms to user",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *     response="200",
     *     description="Send sms successfully"
     * ),
     *          @OA\RequestBody(
     *     required=true,
     *      @OA\JsonContent(
     *          required={"message"},
     *       @OA\Property(property="message", type="string", example="message"),
     *      )
     * ),
     *          @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="User id",
     *      required=true,
     *      @OA\Schema(
     *      type="integer",
     *      )
     *     ),
     * ),
     *
     * @param $id
     * @param SmsRequest $request
     * @return JsonResponse
     */
    public function sms($id, SmsRequest $request): JsonResponse
    {
        Redis::del(HistoryController::$cacheNameNoti);
        $user = User::query()->findOrFail($id);
        // check adb devices
        $list = explode("\n", shell_exec("adb devices"));
        if (count($list) != 4 && !str_contains($list[1], 'device'))
            return response()->json(['error' => "Can't detect device"], 500);
        // run adb cell
        $phone = $user->phone;
        $message = $request->message;
        $command = 'adb shell service call isms 5 i32 2 s16 "com.android.mms.service" s16 "null" s16 "'.$phone.'" s16 "null" s16 "\''.$message.'\'" s16 "null" s16 "null" i32 0 i64 0';
        shell_exec($command); # tested on Samsung M21 with android 11 sim #2
        Notify::query()->create([
            'from' => auth()->id(),
            'to' => $user->id,
            'address' => $user->phone,
            'content' => $request->message,
            'kind' => NotiType::SMS,
        ]);
        return response()->json(['message' => 'Send sms successfully']);
    }

    private function revokeToken($id)
    {
        $tokens = Redis::keys('Auth:*');
        foreach ($tokens as $token) {
            $token = explode(':', $token)[1];
            if (json_decode(Redis::get("Auth:$token"))->id == $id)
                Redis::del("Auth:$token");
        }
    }
}
