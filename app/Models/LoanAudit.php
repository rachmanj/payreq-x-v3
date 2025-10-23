<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanAudit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'loan_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
        'notes'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getActionLabelAttribute()
    {
        $labels = [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'status_changed' => 'Status Changed',
            'installments_generated' => 'Installments Generated',
        ];

        return $labels[$this->action] ?? ucfirst($this->action);
    }

    public function getChangesSummaryAttribute()
    {
        if ($this->action === 'created') {
            return 'New loan created';
        }

        if ($this->action === 'deleted') {
            return 'Loan deleted';
        }

        if ($this->action === 'status_changed') {
            $oldStatus = $this->old_values['status'] ?? 'unknown';
            $newStatus = $this->new_values['status'] ?? 'unknown';
            return "Status changed from {$oldStatus} to {$newStatus}";
        }

        if ($this->action === 'installments_generated') {
            $count = $this->new_values['count'] ?? 0;
            return "{$count} installments generated";
        }

        if ($this->action === 'updated') {
            $changes = [];
            foreach ($this->new_values as $field => $newValue) {
                $oldValue = $this->old_values[$field] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[] = "{$field}: {$oldValue} → {$newValue}";
                }
            }
            return implode(', ', $changes);
        }

        return 'Changes made';
    }
}
