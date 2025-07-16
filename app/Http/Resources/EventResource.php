<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'description'    => $this->description,
            'location'       => $this->location,
            'start_time'     => $this->start_time->toDateTimeString(),
            'end_time'       => $this->end_time->toDateTimeString(),
            'host'           => $this->host,
            'participants'   => $this->participants,
            'reminder_type'  => $this->reminder_type,
            'reminder_time'  => optional($this->reminder_time)->toDateTimeString(),
            'attachments'    => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at'     => $this->created_at->toDateTimeString(),
            'updated_at'     => $this->updated_at->toDateTimeString(),
        ];
    }
}
