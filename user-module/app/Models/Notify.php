<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notify extends Model
{
    use HasFactory;
    protected $table = 'notifies';
    protected $fillable = [
        'from',
        'to',
        'address',
        'content',
        'kind'
    ];
    public $timestamps = false;
}
