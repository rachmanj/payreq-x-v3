<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchGroupSapLine extends Model
{
    protected $guarded = [];

    public function reconciliationMatchGroup(): BelongsTo
    {
        return $this->belongsTo(ReconciliationMatchGroup::class);
    }

    public function sapGlLine(): BelongsTo
    {
        return $this->belongsTo(SapGlLine::class);
    }
}
