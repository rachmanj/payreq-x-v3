<div class="card">
    <div class="card-header">
        <a href="{{ route('cashier.pcbc.index', ['page' => 'dashboard']) }}"
            class="{{ request()->get('page') == 'dashboard' ? 'active' : '' }}">
            Dashboard
        </a> |
        <a href="{{ route('cashier.pcbc.index', ['page' => 'upload']) }}"
            class="{{ request()->get('page') == 'upload' ? 'active' : '' }}">
            Upload
        </a> |
        <a href="{{ route('cashier.pcbc.index', ['page' => 'list']) }}"
            class="{{ request()->get('page') == 'list' ? 'active' : '' }}">
            List
        </a>
    </div> <!-- /.card-header -->
</div> <!-- /.card -->
