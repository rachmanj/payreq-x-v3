<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingChunk extends Model
{
    /** @use HasFactory<\Database\Factories\MeetingChunkFactory> */
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'chunk_index',
        'content',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}
