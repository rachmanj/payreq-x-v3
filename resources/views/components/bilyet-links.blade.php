<div class="card">
    <div class="card-header">
        <a href="{{ route('cashier.bilyets.index', ['page' => 'dashboard']) }}"
            class="{{ request()->get('page') == 'dashboard' ? 'active' : '' }}">Dashboard</a> |
        <a href="{{ route('cashier.bilyets.index', ['page' => 'onhand']) }}"
            class="{{ request()->get('page') == 'onhand' ? 'active' : '' }}">Onhand</a> |
        <a href="{{ route('cashier.bilyets.index', ['page' => 'release']) }}"
            class="{{ request()->get('page') == 'release' ? 'active' : '' }}">Release</a> |
        <a href="{{ route('cashier.bilyets.index', ['page' => 'cair']) }}"
            class="{{ request()->get('page') == 'cair' ? 'active' : '' }}">Cair</a> |
        <a href="{{ route('cashier.bilyets.index', ['page' => 'void']) }}"
            class="{{ request()->get('page') == 'void' ? 'active' : '' }}">Void</a> |
        <a href="{{ route('cashier.bilyets.index', ['page' => 'upload']) }}"
            class="{{ request()->get('page') == 'upload' ? 'active' : '' }}">Upload</a>
    </div>
</div>
