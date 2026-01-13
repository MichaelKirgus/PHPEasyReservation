<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'lang',
        'name',
        'value',
    ];
}
