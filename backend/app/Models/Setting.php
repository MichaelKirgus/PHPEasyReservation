<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'name';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'value',
    ];
}
