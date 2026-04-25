<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'short_name'])]
class Club extends Model
{
    use HasFactory;

    public function members(): HasMany
    {
        return $this->hasMany(ClubMember::class);
    }

    public function readingCycles(): HasMany
    {
        return $this->hasMany(ReadingCycle::class);
    }

    public function bookCandidates(): HasMany
    {
        return $this->hasMany(BookCandidate::class);
    }

    public function turnOrders(): HasMany
    {
        return $this->hasMany(TurnOrder::class);
    }
}
