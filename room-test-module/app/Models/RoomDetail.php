<?php

namespace App\Models;

use App\Enums\ActionType;
use App\Http\Controllers\RoomDetailHistoryController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RoomDetail extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'name',
    ];

    public static function boot(): void
    {
        parent::boot(); // TODO: Change the autogenerated stub
        self::created(function ($model) {
            Redis::del(RoomDetailHistoryController::$cacheNameRoomDetails);
            DB::table('room_detail_histories')->insert([
                'room_detail_id' => $model->id,
                'name' => $model->name,
                'created_by' => get_user()->id ?? "system",
                'status' => ActionType::CREATE
            ]);
        });
        self::updated(function ($model) {
            Redis::del(RoomDetailHistoryController::$cacheNameRoomDetails);
            DB::table('room_detail_histories')->insert([
                'room_detail_id' => $model->id,
                'name' => $model->name,
                'created_by' => get_user()->id ?? "system",
                'status' => ActionType::UPDATE
            ]);
        });
        self::deleting(function ($model) {
            Redis::del(RoomDetailHistoryController::$cacheNameRoomDetails);
            DB::table('room_detail_histories')->insert([
                'room_detail_id' => $model->id,
                'name' => $model->name,
                'created_by' => get_user()->id ?? "system",
                'status' => ActionType::DELETE
            ]);
        });
    }
}
