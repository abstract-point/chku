<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['club_id', 'user_id', 'initials', 'is_active', 'joined_at', 'favorite_genre_id'])]
class ClubMember extends Model
{
    use HasFactory;

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'date',
        'deactivated_at' => 'datetime',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favoriteGenre(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'favorite_genre_id');
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

    public function meetingRsvps(): HasMany
    {
        return $this->hasMany(MeetingRsvp::class);
    }

    public function bookCandidateResponses(): HasMany
    {
        return $this->hasMany(BookCandidateResponse::class);
    }

    public function proposedCycles(): HasMany
    {
        return $this->hasMany(ReadingCycle::class, 'proposer_id');
    }

    public function proposedCandidates(): HasMany
    {
        return $this->hasMany(BookCandidate::class, 'proposer_id');
    }

    public function bookQueueItems(): HasMany
    {
        return $this->hasMany(MemberBookQueueItem::class);
    }

    public function turnOrder(): HasMany
    {
        return $this->hasMany(TurnOrder::class);
    }
}
