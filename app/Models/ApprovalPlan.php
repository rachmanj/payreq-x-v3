<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalPlan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'requestor_remarks_updated_at' => 'datetime',
        'approver_read_requestor_reply_at' => 'datetime',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id')->withDefault([
            'name' => 'N/A',
        ]);
    }

    public function payreq()
    {
        return $this->belongsTo(PayReq::class, 'document_id', 'id');
    }

    public function realization()
    {
        return $this->belongsTo(Realization::class, 'document_id', 'id');
    }

    public function anggaran()
    {
        return $this->belongsTo(Anggaran::class, 'document_id', 'id');
    }

    public function scopeEligibleForApproverRequestorReplyInbox(Builder $query, int $approverId): Builder
    {
        return $query->where('approver_id', $approverId)
            ->whereNotNull('remarks')
            ->where('remarks', '!=', '')
            ->whereNotNull('requestor_remarks')
            ->where('requestor_remarks', '!=', '')
            ->whereNotNull('requestor_remarks_updated_at');
    }

    public static function unreadRequestorReplyCountForApprover(int $approverId): int
    {
        return static::query()
            ->eligibleForApproverRequestorReplyInbox($approverId)
            ->where(function (Builder $q): void {
                $q->whereNull('approver_read_requestor_reply_at')
                    ->orWhereColumn('requestor_remarks_updated_at', '>', 'approver_read_requestor_reply_at');
            })
            ->count();
    }

    public function isApproverUnreadRequestorReply(): bool
    {
        if ($this->requestor_remarks_updated_at === null) {
            return false;
        }

        if (! filled($this->requestor_remarks) || ! filled($this->remarks)) {
            return false;
        }

        if ($this->approver_read_requestor_reply_at === null) {
            return true;
        }

        return $this->requestor_remarks_updated_at->gt($this->approver_read_requestor_reply_at);
    }

    public function markApproverReadRequestorReply(): void
    {
        $this->forceFill([
            'approver_read_requestor_reply_at' => now(),
        ])->save();
    }
}
