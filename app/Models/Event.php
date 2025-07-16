<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'location_id',
        'start_time',
        'end_time',
        'host',
        'participants',
        'status',
        'reminder_type',
        'reminder_time',
        'creator_id',
        'preparer_id'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'reminder_time' => 'datetime',
        'reminder_type' => 'string',
    ];

    public function histories()
    {
        return $this->hasMany(EventHistory::class);
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'event_locations');
    }

    public function preparers()
    {
        return $this->belongsToMany(Department::class, 'event_preparers', 'event_id', 'department_id');
    }

    public function attachments()
    {
        return $this->belongsToMany(Attachment::class, 'event_attachments')
            ->withPivot('added_at')
            ->withTimestamps();
    }
}
