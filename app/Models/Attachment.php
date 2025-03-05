<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use SoftDeletes;

    protected $fillable = ['file_name', 'file_url', 'file_type', 'uploader_id'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_attachments', 'attachment_id', 'event_id')
            ->withPivot('added_at');
    }
}
