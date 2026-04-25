<?php

namespace App\Models;

use App\Enums\ReadingCycleStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['club_id', 'book_id', 'proposer_id', 'cycle_number', 'status', 'discussion_prompt', 'completed_at'])]
class ReadingCycle extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => ReadingCycleStatusEnum::class,
        'completed_at' => 'datetime',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class, 'proposer_id');
    }

    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function discussionMessages(): HasMany
    {
        return $this->hasMany(DiscussionMessage::class);
    }

    public function meeting(): HasOne
    {
        return $this->hasOne(Meeting::class);
    }

    public function bookCandidate(): HasOne
    {
        return $this->hasOne(BookCandidate::class);
    }
}
