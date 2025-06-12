<div class="card">
    <div class="card-header">
        <a href="{{ route('cashier.bilyets.index', ['page' => 'dashboard']) }}"
            class="{{ request()->get('page') == 'dashboard' || request()->get('page') == '' || !request()->get('page') ? 'active' : '' }}">Dashboard</a>
        |
        <a href="{{ route('cashier.bilyets.index', ['page' => 'list']) }}"
            class="{{ request()->get('page') == 'list' ? 'active' : '' }}">List</a> |
        <a href="{{ route('cashier.bilyets.index', ['page' => 'upload']) }}"
            class="{{ request()->get('page') == 'upload' ? 'active' : '' }}">Upload</a>
    </div>
</div>
