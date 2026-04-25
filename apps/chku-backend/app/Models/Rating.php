<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reading_cycle_id', 'club_member_id', 'rating'])]
class Rating extends Model
{
    use HasFactory;

    protected $casts = [
        'rating' => 'integer',
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
