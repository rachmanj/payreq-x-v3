<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchGroupBankLine extends Model
{
    protected $guarded = [];

    public function reconciliationMatchGroup(): BelongsTo
    {
        return $this->belongsTo(ReconciliationMatchGroup::class);
    }

    public function bankStatementLine(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class);
    }
}
