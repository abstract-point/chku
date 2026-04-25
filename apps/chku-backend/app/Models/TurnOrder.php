<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['club_id', 'club_member_id', 'position', 'is_current', 'is_next', 'skipped_at'])]
class TurnOrder extends Model
{
    use HasFactory;

    protected $casts = [
        'is_current' => 'boolean',
        'is_next' => 'boolean',
        'skipped_at' => 'datetime',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function clubMember(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class);
    }
}
