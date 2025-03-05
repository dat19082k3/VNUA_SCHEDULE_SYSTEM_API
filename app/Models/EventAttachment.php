<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EventAttachment extends Pivot
{
    protected $table = 'event_attachments';

    protected $fillable = ['event_id', 'attachment_id', 'added_at'];

    public $timestamps = false;
}
