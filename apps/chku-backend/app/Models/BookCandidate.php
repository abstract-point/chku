<?php

namespace App\Models;

use App\Enums\BookCandidateStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['book_id', 'proposer_id', 'reading_cycle_id', 'reason', 'description', 'status'])]
class BookCandidate extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => BookCandidateStatusEnum::class,
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class, 'proposer_id');
    }

    public function readingCycle(): BelongsTo
    {
        return $this->belongsTo(ReadingCycle::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(BookCandidateResponse::class);
    }
}
