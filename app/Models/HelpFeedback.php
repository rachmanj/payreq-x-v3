<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpFeedback extends Model
{
    /** @use HasFactory<\Database\Factories\HelpFeedbackFactory> */
    use HasFactory;

    protected $table = 'help_feedbacks';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'steps_to_reproduce',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
