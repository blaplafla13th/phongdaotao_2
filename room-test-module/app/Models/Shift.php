<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        "shift_start_time",
        "master",
        "link_start_time",
        "link_end_time",
        "is_active",
    ];
}
