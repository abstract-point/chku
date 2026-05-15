<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['club_id', 'club_member_id', 'next_turn_order_id'])]
class TurnOrder extends Model
{
    use HasFactory;

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function clubMember(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class);
    }

    public function nextTurnOrder(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_turn_order_id');
    }
}
