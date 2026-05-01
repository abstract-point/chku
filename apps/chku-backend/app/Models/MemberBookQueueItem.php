<?php

namespace App\Models;

use App\Enums\MemberBookQueueItemStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['club_member_id', 'book_id', 'position', 'reason', 'description', 'status'])]
class MemberBookQueueItem extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => MemberBookQueueItemStatusEnum::class,
    ];

    public function clubMember(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(BookCandidate::class);
    }
}
