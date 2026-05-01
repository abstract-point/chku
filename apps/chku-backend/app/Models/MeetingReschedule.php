<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meeting_id', 'actor_id', 'old_date', 'old_time', 'new_date', 'new_time', 'reason'])]
class MeetingReschedule extends Model
{
    use HasFactory;

    protected $casts = [
        'old_date' => 'date',
        'new_date' => 'date',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
