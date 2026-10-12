<?php

namespace App\Models;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['reading_cycle_id', 'title', 'date', 'time', 'place', 'address', 'reservation', 'link', 'notes', 'is_online', 'started_at', 'finished_at'])]
class Meeting extends Model
{
    use HasFactory;

    public const MIN_ATTENDING_MEMBERS = 2;

    protected $casts = [
        'date' => 'date',
        'is_online' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function readingCycle(): BelongsTo
    {
        return $this->belongsTo(ReadingCycle::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(MeetingRsvp::class);
    }

    public function reschedules(): HasMany
    {
        return $this->hasMany(MeetingReschedule::class);
    }

    /**
     * Active members who are marked as attending the meeting.
     *
     * @return Collection<int, int>
     */
    public function attendingMemberIds(): Collection
    {
        return $this->rsvps()
            ->where('status', MeetingRsvpStatusEnum::Attending->value)
            ->whereHas('clubMember', fn ($q) => $q->where('is_active', true))
            ->pluck('club_member_id');
    }

    /**
     * Attending members who finished the book: their ratings are required to finish the meeting.
     *
     * @return Collection<int, int>
     */
    public function requiredRaterIds(): Collection
    {
        return ReadingProgress::query()
            ->where('reading_cycle_id', $this->reading_cycle_id)
            ->whereIn('club_member_id', $this->attendingMemberIds())
            ->where('status', ReadingProgressStatusEnum::Finished)
            ->pluck('club_member_id');
    }
}
