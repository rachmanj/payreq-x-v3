<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtilityApInvoice extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(UtilityBill::class);
    }

    public function sapBusinessPartner(): BelongsTo
    {
        return $this->belongsTo(SapBusinessPartner::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
