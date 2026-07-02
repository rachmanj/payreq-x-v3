<div class="card">
    <div class="card-header">
        <a href="{{ route('cashier.koran.index', ['page' => 'dashboard']) }}"
            class="{{ request()->get('page', 'dashboard') == 'dashboard' ? 'active' : '' }}">
            Dashboard
        </a>
    </div> <!-- /.card-header -->
</div> <!-- /.card -->
