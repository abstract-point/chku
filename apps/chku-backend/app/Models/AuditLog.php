<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['actor_id', 'target_member_id', 'action', 'metadata'])]
class AuditLog extends Model
{
    use HasFactory;

    protected $casts = [
        'metadata' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function targetMember(): BelongsTo
    {
        return $this->belongsTo(ClubMember::class, 'target_member_id');
    }
}
