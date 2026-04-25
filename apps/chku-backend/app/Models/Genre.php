<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name'])]
class Genre extends Model
{
    use HasFactory;

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function favoriteMembers(): HasMany
    {
        return $this->hasMany(ClubMember::class, 'favorite_genre_id');
    }
}
