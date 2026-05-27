<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'slug' => $this->slug,
            'description' => $this->description,
            'coverColor' => $this->cover_color,
            'coverUrl' => $this->cover_url,
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
        ];
    }
}
