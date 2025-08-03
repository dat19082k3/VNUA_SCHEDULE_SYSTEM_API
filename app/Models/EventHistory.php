<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'field_name',
        'old_value',
        'new_value',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'old_value' => 'string',
        'new_value' => 'string',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
