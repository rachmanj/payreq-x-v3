<div class="card">
    <div class="card-header">
        @php
            $projects = [
                'dashboard' => 'Dashboard',
                '000H' => 'HO & APS',
                '001H' => 'BO Jkt',
                '017C' => '017C',
                '021C' => '021C',
                '022C' => '022C',
                '023C' => '023C',
            ];
        @endphp

        @foreach ($projects as $key => $label)
            @php
                $count = \App\Models\VerificationJournal::where('project', $key)->whereNull('sap_journal_no')->count();
            @endphp
            <a href="{{ route('accounting.sap-sync.index', ['page' => $key]) }}"
                class="{{ request()->get('page') == $key ? 'active' : '' }}">{{ $label }}</a>
            @if (!$loop->last)
                <span class="badge badge-danger">{{ $count > 0 ? $count : '' }}</span> |
            @else
                <span class="badge badge-danger">{{ $count > 0 ? $count : '' }}</span>
            @endif
        @endforeach
    </div>
</div>
