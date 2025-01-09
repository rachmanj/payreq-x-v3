<div class="card">
    <div class="card-header">
        <a href="{{ route('accounting.deliveries.index', ['page' => 'dashboard']) }}"
            class="{{ request()->get('page') == 'dashboard' ? 'active' : '' }}">Dashboard</a>
        | <a href="{{ route('accounting.deliveries.index', ['page' => 'create']) }}"
            class="{{ request()->get('page') == 'create' ? 'active' : '' }}">New</a>
        | <a href="{{ route('accounting.deliveries.index', ['page' => 'list']) }}"
            class="{{ request()->get('page') == 'list' ? 'active' : '' }}">List</a>
        @if (auth()->user()->project === '000H')
            | <a href="{{ route('accounting.deliveries.index', ['page' => 'receive']) }}"
                class="{{ request()->get('page') == 'receive' ? 'active' : '' }}">Receive</a>
        @endif
    </div>
</div>
