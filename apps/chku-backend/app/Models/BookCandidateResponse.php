<?php

namespace App\Models;

use App\Enums\BookCandidateResponseEnum as ResponseEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['book_candidate_id', 'club_member_id', 'response'])]
class BookCandidateResponse extends Model
{
    use HasFactory;

    protected $casts = [
        'response' => ResponseEnum::class,
    ];

    public function bookCandidate(): BelongsTo
    {
        return $this->belongsTo(BookCandidate::class);
    }

    public function clubMember(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class);
    }
}
