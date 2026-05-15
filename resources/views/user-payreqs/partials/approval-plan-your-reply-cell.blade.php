<td>
    @if (filled($item->remarks))
        @if (filled($item->requestor_remarks))
            <div class="small">{{ $item->requestor_remarks }}</div>
        @else
            <div class="requestor-reply-wrap">
                <textarea class="form-control form-control-sm requestor-remark-input mb-1" rows="2"></textarea>
                <button type="button" class="btn btn-sm btn-primary save-requestor-remark"
                    data-url="{{ route('approvals.plan.requestor-remarks.update', $item->id) }}">Save</button>
            </div>
        @endif
    @else
        <span class="text-muted">{{ $item->requestor_remarks ?: '—' }}</span>
    @endif
</td>
