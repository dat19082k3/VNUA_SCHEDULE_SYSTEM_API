<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uid',
        'file_name',
        'file_url',
        'file_type',
        'size',
        'uploader_id',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_attachments')
                    ->withPivot('added_at')
                    ->withTimestamps();
    }
}
