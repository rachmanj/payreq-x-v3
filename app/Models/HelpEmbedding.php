<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpEmbedding extends Model
{
    /** @use HasFactory<\Database\Factories\HelpEmbeddingFactory> */
    use HasFactory;

    protected $fillable = [
        'chunk_key',
        'source_path',
        'heading',
        'locale',
        'content',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];
}
