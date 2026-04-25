<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['book_id', 'cover_path', 'cover_mime', 'cover_width', 'cover_height', 'cover_size', 'cover_source'])]
class BookCover extends Model
{
    use HasFactory;

    protected $casts = [
        'cover_width' => 'integer',
        'cover_height' => 'integer',
        'cover_size' => 'integer',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
