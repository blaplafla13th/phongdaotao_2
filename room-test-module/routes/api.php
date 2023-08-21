<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\RoomDetailController;
use App\Http\Controllers\RoomDetailHistoryController;
use App\Http\Controllers\RoomTestController;
use App\Http\Controllers\ShiftController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckEmployee;
use App\Http\Middleware\CheckTeacher;
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
Route::group([
    'prefix' => 'rooms',
    'middleware' => CheckEmployee::class,
], function ($router) {
    Route::post('/template', [RoomDetailController::class, 'import']);
    Route::get('/template', [RoomDetailController::class, 'getTemplate']);
    Route::post('/', [RoomDetailController::class, 'store']);
    Route::put('/{id}', [RoomDetailController::class, 'update']);
    Route::delete('/{id}', [RoomDetailController::class, 'destroy']);
});
Route::group([
    'prefix' => 'rooms',
], function ($router) {
    Route::get('/', [RoomDetailController::class, 'index']);
    Route::get('/{id}', [RoomDetailController::class, 'show']);
})->withoutMiddleware([CheckAdmin::class]);

Route::group([
    'prefix' => 'history',
    'middleware' => CheckAdmin::class,
], function ($router) {
    Route::get('/rooms', [RoomDetailHistoryController::class, 'showHistoryRoomDetail']);
    Route::get('/rooms/{id}', [RoomDetailHistoryController::class, 'detailHistoryRoomDetail']);
    Route::put('/rooms/{id}', [RoomDetailHistoryController::class, 'restoreRoomDetail']);
});

Route::group([
    'prefix' => 'assignments',
    'middleware' => CheckEmployee::class,
], function ($router) {
    Route::get('/template', [AssignmentController::class, 'getTemplate']);
    Route::post('/template/{id}', [AssignmentController::class, 'import']);
    Route::post('/', [AssignmentController::class, 'store']);
    Route::put('/{id}', [AssignmentController::class, 'update']);
    Route::delete('/{id}', [AssignmentController::class, 'destroy']);
});
Route::group([
    'prefix' => 'assignments',
], function ($router) {
    Route::get('/', [AssignmentController::class, 'index']);
    Route::get('/{id}', [AssignmentController::class, 'show']);
})->withoutMiddleware([CheckEmployee::class]);

Route::group([
    'prefix' => 'shifts',
], function ($router) {
    Route::get('/', [ShiftController::class, 'index']);
    Route::get('/{id}', [ShiftController::class, 'show']);
    Route::put('/start', [ShiftController::class, 'start']);
})->withoutMiddleware([CheckEmployee::class]);
Route::group([
    'prefix' => 'shifts',
    'middleware' => CheckEmployee::class,
], function ($router) {
    Route::post('/', [ShiftController::class, 'store']);
    Route::post('/start', [ShiftController::class, 'start']);
    Route::put('/{id}', [ShiftController::class, 'update']);
    Route::delete('/{id}', [ShiftController::class, 'destroy']);
});

Route::group([
    'prefix' => 'tests',
    'middleware' => CheckEmployee::class,
], function ($router) {
    Route::post('/import', [RoomTestController::class, 'import']);
    Route::post('/', [RoomTestController::class, 'store']);
    Route::put('/{id}', [RoomTestController::class, 'update']);
    Route::delete('/{id}', [RoomTestController::class, 'destroy']);
});

Route::group([
    'prefix' => 'tests',
], function ($router) {
    Route::get('/', [RoomTestController::class, 'index']);
    Route::get('/{id}', [RoomTestController::class, 'show']);
})->withoutMiddleware([CheckEmployee::class]);



Route::group([
    'prefix' => 'checkin',
    'middleware' => CheckTeacher::class,
], function ($router) {
    Route::get('/join/{id}', [CheckinController::class, 'join']);
    Route::post('/summary', [CheckinController::class, 'summary']);
});

Route::group([
    'prefix' => 'checkin',
    'middleware' => CheckEmployee::class,
], function ($router) {
    Route::get('/', [CheckinController::class, 'index']);
    Route::post('/{id}', [CheckinController::class, 'create']);
    Route::put('/{id}/{id_checkin}', [CheckinController::class, 'update']);
});

Route::get('/clear', [Controller::class, 'clear_cache'])->middleware(CheckAdmin::class);
