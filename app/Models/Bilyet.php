<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bilyet extends Model
{
    use HasFactory;

    protected $fillable = [
        'giro_id',
        'prefix',
        'nomor',
        'type',
        'bilyet_date',
        'cair_date',
        'receive_date',
        'amount',
        'remarks',
        'filename',
        'loan_id',
        'created_by',
        'project',
        'status'
    ];

    protected $casts = [
        'bilyet_date' => 'date',
        'cair_date' => 'date',
        'receive_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Business logic constants
    const STATUSES = ['onhand', 'release', 'cair', 'void'];
    const TYPES = ['cek', 'bilyet', 'loa'];

    const STATUS_LABELS = [
        'onhand' => 'On Hand',
        'release' => 'Released',
        'cair' => 'Settled',
        'void' => 'Voided'
    ];

    const TYPE_LABELS = [
        'cek' => 'Check',
        'bilyet' => 'Bilyet Giro',
        'loa' => 'Letter of Authority'
    ];

    // Relationships
    public function giro()
    {
        return $this->belongsTo(Giro::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function audits()
    {
        return $this->hasMany(BilyetAudit::class);
    }

    // Business logic methods
    public function canBeVoided()
    {
        return $this->status !== 'cair';
    }

    public function canTransitionTo($newStatus)
    {
        $allowedTransitions = [
            'onhand' => ['release', 'void'],
            'release' => ['cair', 'void'],
            'cair' => [], // Cannot transition from cair
            'void' => [] // Cannot transition from void
        ];

        return in_array($newStatus, $allowedTransitions[$this->status] ?? []);
    }

    public function getStatusLabelAttribute()
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getTypeLabelAttribute()
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getFullNomorAttribute()
    {
        return $this->prefix . $this->nomor;
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByProject($query, $project)
    {
        return $query->where('project', $project);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOnhand($query)
    {
        return $query->where('status', 'onhand');
    }

    public function scopeReleased($query)
    {
        return $query->where('status', 'release');
    }

    public function scopeSettled($query)
    {
        return $query->where('status', 'cair');
    }

    public function scopeVoided($query)
    {
        return $query->where('status', 'void');
    }

    public function scopeWithAmount($query)
    {
        return $query->whereNotNull('amount')->where('amount', '>', 0);
    }
}
