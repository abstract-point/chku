<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['reading_cycle_id', 'title', 'date', 'time', 'place', 'address', 'reservation', 'link', 'topics', 'notes', 'is_online', 'started_at', 'finished_at'])]
class Meeting extends Model
{
    use HasFactory;

    protected $casts = [
        'date' => 'date',
        'topics' => 'array',
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
}
