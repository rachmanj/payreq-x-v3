<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotulenQuestion extends Model
{
    /** @use HasFactory<\Database\Factories\NotulenQuestionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'question',
        'answer',
        'sources',
        'model',
        'top_score',
        'latency_ms',
        'not_found',
        'created_at',
    ];

    protected $casts = [
        'sources' => 'array',
        'top_score' => 'float',
        'latency_ms' => 'integer',
        'not_found' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
