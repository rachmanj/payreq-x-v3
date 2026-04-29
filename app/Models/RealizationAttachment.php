<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealizationAttachment extends Model
{
    protected $guarded = [];

    public function realization(): BelongsTo
    {
        return $this->belongsTo(Realization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
