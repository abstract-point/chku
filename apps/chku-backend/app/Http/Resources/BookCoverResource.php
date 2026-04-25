<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookCoverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'path' => $this->cover_path,
            'mime' => $this->cover_mime,
            'width' => $this->cover_width,
            'height' => $this->cover_height,
            'size' => $this->cover_size,
            'source' => $this->cover_source,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
