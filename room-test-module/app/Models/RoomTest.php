<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomTest extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'room_detail_id',
        'shift_id',
        "quantity",
        'exam_test_id',
        'need_supervisor',
        'supervisor1',
        'supervisor2',
        'supervisor3',
    ];
}
