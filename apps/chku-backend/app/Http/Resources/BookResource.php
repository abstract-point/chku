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
            'isbn' => $this->isbn,
            'description' => $this->description,
            'coverColor' => $this->cover_color,
            'genre' => new GenreResource($this->whenLoaded('genre')),
            'covers' => BookCoverResource::collection($this->whenLoaded('covers')),
        ];
    }
}
