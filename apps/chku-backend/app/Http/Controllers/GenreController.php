<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\GenreResource;
use App\Models\Genre;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GenreController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return GenreResource::collection(
            Genre::query()->orderBy('name')->get()
        );
    }
}
