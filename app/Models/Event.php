<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'description', 'location', 'start_time', 'end_time',
        'host', 'participants', 'reminder_type', 'reminder_time'
    ];

    public function attachments()
    {
        return $this->belongsToMany(Attachment::class, 'event_attachments', 'event_id', 'attachment_id')
            ->withPivot('added_at');
    }
}
