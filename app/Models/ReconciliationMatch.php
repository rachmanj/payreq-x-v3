<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationMatch extends Model
{
    public const TYPE_AUTO_EXACT = 'auto_exact';

    public const TYPE_AUTO_FUZZY = 'auto_fuzzy';

    public const TYPE_MANUAL = 'manual';

    protected $guarded = [];

    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function bankStatementLine(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class);
    }

    public function sapGlLine(): BelongsTo
    {
        return $this->belongsTo(SapGlLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
