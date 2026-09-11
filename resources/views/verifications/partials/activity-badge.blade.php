@php
    $context = $context ?? 'row';
    $headerActivity = $headerActivity ?? ($realization->activity ?? null);
@endphp

@if ($context === 'header')
    @php
        $activity = $effectiveActivity ?? $realization->activity;
    @endphp
    @if ($activity)
        <span class="vj-chip vj-chip-info">
            <i class="fas fa-tags"></i>
            Kegiatan: {{ $activity->code }} — {{ $activity->name }}
        </span>
        @if ($activity->isReklasifikasi() && $activity->account)
            <span class="vj-chip vj-chip-neutral">
                Reklasifikasi → {{ $activity->account->account_number }} — {{ $activity->account->account_name }}
            </span>
        @else
            <span class="vj-chip vj-chip-neutral">
                Tanpa reklasifikasi (akun asli dipertahankan)
            </span>
        @endif
    @else
        <span class="vj-chip vj-chip-neutral">
            <i class="fas fa-tags"></i>
            Tanpa kegiatan
        </span>
    @endif
@else
    @if ($detail->activity_excluded)
        <div class="mt-1">
            <span class="vj-chip vj-chip-neutral">Tanpa kegiatan (override)</span>
        </div>
    @elseif ($detail->activity)
        @php
            $rowActivity = $effectiveActivity ?? $detail->activity;
        @endphp
        <div class="mt-1">
            <span class="vj-chip vj-chip-info">{{ $rowActivity->code }} — {{ $rowActivity->name }}</span>
            @if ($rowActivity->isReklasifikasi())
                @if ($rowActivity->account)
                    <span class="vj-chip vj-chip-neutral d-inline-flex ml-1">
                        → {{ $rowActivity->account->account_number }} — {{ $rowActivity->account->account_name }}
                    </span>
                @endif
                @if (! $detail->account_id)
                    <small class="text-muted d-block">akun belum dipilih</small>
                @endif
            @endif
        </div>
    @elseif ($headerActivity)
        <div class="mt-1">
            <span class="vj-chip vj-chip-neutral" style="background: transparent;">
                Ikut kegiatan: {{ $headerActivity->code }} — {{ $headerActivity->name }}
            </span>
        </div>
    @endif
@endif
