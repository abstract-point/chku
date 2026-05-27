<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'title',
    'author',
    'slug',
    'description',
    'cover_color',
])]
class Book extends Model
{
    use HasFactory;

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function covers(): HasMany
    {
        return $this->hasMany(BookCover::class);
    }

    public function primaryCover(): HasOne
    {
        return $this->hasOne(BookCover::class)->latestOfMany();
    }

    public function readingCycles(): HasMany
    {
        return $this->hasMany(ReadingCycle::class);
    }

    public function bookCandidates(): HasMany
    {
        return $this->hasMany(BookCandidate::class);
    }

    public function memberQueueItems(): HasMany
    {
        return $this->hasMany(MemberBookQueueItem::class);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if ($this->relationLoaded('primaryCover') && $this->primaryCover) {
            return '/storage/'.$this->primaryCover->cover_path;
        }

        return null;
    }
}
