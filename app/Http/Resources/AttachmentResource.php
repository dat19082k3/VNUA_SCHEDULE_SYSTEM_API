<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'file_name'  => $this->file_name,
            'file_url'   => $this->file_url,
            'file_type'  => $this->file_type,
            'uploader_id'=> $this->uploader_id,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
