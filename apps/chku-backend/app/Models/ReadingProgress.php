<?php

namespace App\Models;

use App\Enums\ReadingProgressStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reading_cycle_id', 'club_member_id', 'status', 'progress_percent', 'current_page', 'notes', 'finished_at'])]
class ReadingProgress extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => ReadingProgressStatusEnum::class,
        'progress_percent' => 'integer',
        'current_page' => 'integer',
        'finished_at' => 'datetime',
    ];

    public function readingCycle(): BelongsTo
    {
        return $this->belongsTo(ReadingCycle::class);
    }

    public function clubMember(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class);
    }
}
