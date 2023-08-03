<?php
namespace App\Http\Controllers;
use App\Enums\ActionType;
use App\Enums\NotiType;
use App\Enums\UserType;
use App\Http\Request\MailRequest;
use App\Http\Requests\Auth\CreateAccountRequest;
use App\Http\Requests\Auth\UpdateAccountRequest;
use App\Models\Department;
use App\Models\Notify;
use App\Models\User;
use App\Models\UserHistory;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Process\Process;

class UserController extends Controller{
    protected function createAccount(CreateAccountRequest $request): JsonResponse
    {
        (new User())->query()->create($request->validated());
        return response()->json(['message' => 'User successfully created']);
    }

    protected function updateAccount(UpdateAccountRequest $request, $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        if ($request->password !== null || $request->password !== '')
            $request->merge(['password' => bcrypt($request->password)]);
        $user->update($request->validated());
        return response()->json(['message' => 'User successfully updated']);
    }

    protected function deleteAccount($id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        if (auth()->id() === $user->id)
            return response()->json(['message' => 'You cannot delete your own account'], 403);
        $user->delete();
        return response()->json(['message' => 'User successfully deleted']);
    }

    protected function getAccount($id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        return response()->json($user);
    }

    protected function searchAccount(Request $request): JsonResponse
    {
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
        $users = $users->paginate($request->size??10, ['id','name','email','phone','role'], 'page', $request->page??0);
        return response()->json($users);
    }

    protected function excelTemplate()
    {
        // $header is an array containing column headers
        $header = [array("STT","Họ và tên","Email","Số điện thoại","Vai trò","Khoa")];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($header, NULL, 'A1');

        // redirect output to client browser
        header('Content-Disposition: attachment;filename="template.xlsx"');
        header('Cache-Control: max-age=0');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $writer = new Xlsx($spreadsheet);
        try {
            $writer->save('php://output');
        } catch (Exception $e) {
            return response()->json(['message' => 'Error when downloading file'], 500);
        }
    }

    protected function importAccount(Request $request): JsonResponse
    {
        $file = $request->file('file');
        $file->storeAs('',Carbon::now()->timestamp."_UserImport_".auth()->user()->id.".xlsx",'s3');
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        try {
            $spreadsheet = $reader->load($file);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
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
                'content' => "Mật khẩu đăng nhập hệ thống HUS",
                'type' => NotiType::Email,
            ]);
        }
        return response()->json([
            'message' => 'Import successfully',
            'log' => $log
            ]);
    }

    public function mail($id, MailRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        Mail::raw($request->body, function ($message) use ($request, $user) {
            $message->to($user->email, $user->name)
                ->subject($request->subject);
        });
        Notify::query()->create([
            'from' => auth()->id(),
            'to' => $user->id,
            'content' => $request->subject.": ".$request->body,
            'type' => NotiType::Email,
        ]);
        return response()->json(['message' => 'Send mail successfully']);
    }

    public function sms($id, SmsRequest $request): JsonResponse
    {
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
            'content' => $request->message,
            'type' => NotiType::SMS,
        ]);
        return response()->json(['message' => 'Send sms successfully']);
    }

}
