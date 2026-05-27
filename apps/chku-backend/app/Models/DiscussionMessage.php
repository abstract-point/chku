<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['reading_cycle_id', 'club_member_id', 'text', 'parent_id'])]
class DiscussionMessage extends Model
{
    use HasFactory;

    public function readingCycle(): BelongsTo
    {
        return $this->belongsTo(ReadingCycle::class);
    }

    public function clubMember(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function getCanReplyAttribute(): bool
    {
        return $this->parent_id === null;
    }
}
