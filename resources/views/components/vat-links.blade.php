<div class="card">
    <div class="card-header">
        <a href="{{ route('accounting.vat.index', ['page' => 'dashboard', 'status' => $status]) }}"
            class="{{ request()->get('page') == 'dashboard' ? 'active' : '' }}">
            Dashboard
        </a> |
        <a href="{{ route('accounting.vat.index', ['page' => 'search', 'status' => $status]) }}"
            class="{{ request()->get('page') == 'search' ? 'active' : '' }}">
            Search
        </a> |
        <a href="{{ route('accounting.vat.index', ['page' => 'purchase', 'status' => $status]) }}"
            class="{{ request()->get('page') == 'purchase' ? 'active' : '' }}">
            Purchase
        </a> |
        <a href="{{ route('accounting.vat.index', ['page' => 'sales', 'status' => $status]) }}"
            class="{{ request()->get('page') == 'sales' ? 'active' : '' }}">
            Sales
        </a>
        @can('view_tax_monitoring')
            | <a href="{{ route('accounting.tax.ppn.sync.index') }}">PPN Sync SAP</a>
        @endcan
    </div> <!-- /.card-header -->
</div> <!-- /.card -->
