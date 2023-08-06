<?php

namespace App\Models;

use App\Enums\ActionType;
use App\Http\Controllers\HistoryController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';

    protected $fillable = [
        'name',
    ];
    public static function boot(): void
    {
        parent::boot(); // TODO: Change the autogenerated stub
        self::created(function ($model) {
            Redis::del(HistoryController::$cacheNameDepartments);
            DB::table('department_histories')->insert([
                'department_id' => $model->id,
                'name' => $model->name,
                'created_by' => auth()->user()->id??"system",
                'status' => ActionType::CREATE
            ]);
        });
        self::updated(function ($model) {
            Redis::del(HistoryController::$cacheNameDepartments);
            DB::table('department_histories')->insert([
                'department_id' => $model->id,
                'name' => $model->name,
                'created_by' => auth()->user()->id??"system",
                'status' => ActionType::UPDATE
            ]);
        });
        self::deleting(function ($model) {
            Redis::del(HistoryController::$cacheNameDepartments);
            DB::table('department_histories')->insert([
                'department_id' => $model->id,
                'name' => $model->name,
                'created_by' => auth()->user()->id??"system",
                'status' => ActionType::DELETE
            ]);
        });
    }
}