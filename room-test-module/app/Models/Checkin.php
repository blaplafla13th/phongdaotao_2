<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checkin extends Model
{
    use HasFactory;

    public $timestamps = false;
    public $fillable = ['supervisor', 'shift_id', 'check', 'position'];
}
