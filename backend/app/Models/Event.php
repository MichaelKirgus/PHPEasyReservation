<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'city',
        'url',
        'public_transport_url',
        'start_at',
        'end_at',
        'location',
        'capacity_override',
        'active',
        'notes',
        'auto_close_minutes_before',
        'auto_email_template_id',
        'auto_email_offset_minutes_before',
        'auto_email_sent_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'active' => 'boolean',
        'auto_email_sent_at' => 'datetime',
    ];
}
