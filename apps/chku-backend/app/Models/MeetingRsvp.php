<?php

namespace App\Models;

use App\Enums\MeetingRsvpStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meeting_id', 'club_member_id', 'status'])]
class MeetingRsvp extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => MeetingRsvpStatusEnum::class,
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function clubMember(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class);
    }
}
