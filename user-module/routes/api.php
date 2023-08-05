<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    Route::post('/change-pass', [AuthController::class, 'changePassword']);
});

Route::group([
    'prefix' => 'users',
], function($router){
    Route::get('/{id}', [UserController::class, 'getAccount']);
    Route::get('/', [UserController::class, 'searchAccount']);
})->withoutMiddleware([CheckAdmin::class]);

Route::group([
    'prefix' => 'departments',
], function($router){
    Route::get('/', [DepartmentController::class, 'index']);
    Route::get('/{id}', [DepartmentController::class, 'show']);
})->withoutMiddleware([CheckAdmin::class]);

Route::group([
    'prefix' => 'history',
    'middleware' => CheckAdmin::class,
], function($router){
    Route::get('/users', [HistoryController::class, 'showHistoryUser']);
    Route::get('/users/{id}', [HistoryController::class, 'detailHistoryUser']);
    Route::put('/users/{id}', [HistoryController::class, 'restoreUser']);
    Route::get('/departments',[HistoryController::class, 'showHistoryDepartment']);
    Route::get('/departments/{id}', [HistoryController::class, 'detailHistoryDepartment']);
    Route::put('/departments/{id}', [HistoryController::class, 'restoreDepartment']);
});

Route::group([
    'prefix' => 'history',
    'middleware' => CheckEmployee::class,
], function($router){
    Route::get('/notifications', [HistoryController::class, 'showHistoryNotification']);
});

Route::group([
    'prefix' => 'users',
    'middleware' => CheckAdmin::class,
], function($router){
    Route::post('/', [UserController::class, 'createAccount']);
    Route::put('/{id}', [UserController::class, 'updateAccount']);
    Route::delete('/{id}', [UserController::class, 'deleteAccount']);
});

Route::group([
    'prefix' => 'users',
    'middleware' => CheckEmployee::class,
], function($router){
    Route::post('/send-mail/{id}', [UserController::class, 'mail']);
    Route::post('/send-sms/{id}', [UserController::class, 'sms']);
});

Route::group([
    'prefix' => 'departments',
    'middleware' => CheckAdmin::class,
], function($router){
    Route::post('/', [DepartmentController::class, 'store']);
    Route::put('/{id}', [DepartmentController::class, 'update']);
    Route::delete('/{id}', [DepartmentController::class, 'destroy']);
});

Route::group([
    'prefix' => 'template',
    'middleware' => CheckAdmin::class,
], function ($router) {
    Route::post('/account', [UserController::class, 'importAccount']);
    Route::get('/account', [UserController::class, 'excelTemplate']);
    Route::post('/department', [DepartmentController::class, 'import']);
    Route::get('/department', [DepartmentController::class, 'getTemplate']);
});
