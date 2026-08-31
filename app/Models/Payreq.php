<?php

namespace App\Models;

use App\Support\PayreqBudgetLinkMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payreq extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'submit_at' => 'datetime',
    ];

    public function approval_plans()
    {
        return $this->hasMany(ApprovalPlan::class, 'document_id')
            ->where('document_type', 'payreq');
    }

    public function requestor()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault([
            'name' => 'n/a',
        ]);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function outgoings()
    {
        return $this->hasMany(Outgoing::class);
    }

    public function realization()
    {
        return $this->hasOne(Realization::class);
    }

    public function overdueExtensions()
    {
        return $this->hasMany(OverdueExtension::class, 'document_id')
            ->where('document_type', OverdueExtension::DOCUMENT_PAYREQ);
    }

    public function rab()
    {
        return $this->belongsTo(Rab::class, 'rab_id', 'id')->withDefault([
            'rab_no' => 'n/a',
        ]);
    }

    public function realization_details()
    {
        return $this->hasMany(RealizationDetail::class, 'payreq_id', 'id');
    }

    public function last_outgoing()
    {
        $outgoings = $this->outgoings;

        // check if payreq amount === sum of outgoings
        if ($outgoings->sum('amount') < $this->amount) {
            return null;
        } else {
            $lastOutgoing = $outgoings->sortByDesc('outgoing_date')->first();

            // return $lastOutgoing->outgoing_date;
            return $lastOutgoing;
        }
    }

    public function anggaran()
    {
        return $this->belongsTo(Anggaran::class, 'rab_id', 'id');
    }

    public function anggaranAllocations()
    {
        return $this->hasMany(PayreqAnggaranAllocation::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isAdvanceMultiBudget(): bool
    {
        return $this->type === 'advance'
            && $this->budget_link_mode === PayreqBudgetLinkMode::MULTI_ALLOCATION;
    }

    public function allocatedAnggaranIds(): array
    {
        return $this->anggaranAllocations()
            ->pluck('anggaran_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function PayreqMigrasi()
    {
        return $this->hasOne(PayreqMigrasi::class);
    }

    public function transferAccount()
    {
        return $this->belongsTo(TransferAccount::class);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'transfer' => 'Transfer',
            'cash' => 'Cash',
            default => '-',
        };
    }

    public function getPaymentMethodBadgeAttribute(): string
    {
        $tooltip = '';
        if ($this->payment_method === 'transfer' && $this->transferAccount) {
            $tooltip = ' title="'.e($this->transferAccount->displayLabel).'"';
        }

        return match ($this->payment_method) {
            'transfer' => '<span class="badge badge-info"'.$tooltip.'>Transfer</span>',
            'cash' => '<span class="badge badge-secondary">Cash</span>',
            default => '<span class="badge badge-light">-</span>',
        };
    }
}
