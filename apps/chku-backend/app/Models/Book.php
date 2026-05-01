<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['genre_id', 'title', 'author', 'slug', 'isbn', 'description', 'cover_color'])]
class Book extends Model
{
    use HasFactory;

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
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
}
