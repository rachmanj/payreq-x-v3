<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxPeriod extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'snapshot_json' => 'array',
        'audit_log' => 'array',
        'prepared_at' => 'datetime',
        'approved_at' => 'datetime',
        'filed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isMutable(): bool
    {
        return ! in_array($this->status, ['filed', 'locked'], true);
    }
}
