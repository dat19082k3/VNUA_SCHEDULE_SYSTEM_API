<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventCollection extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'data' => EventResource::collection($this->collection->loadMissing('attachments')),
        ];
    }

    public function with($request): array
    {
        return [
            'meta' => [
                'count' => $this->collection->count(),
            ],
        ];
    }
}
