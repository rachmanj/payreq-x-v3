@extends('templates.main')

@section('title_page')
    OP Umum (Pinbuk Bank ke Cash)
@endsection

@section('breadcrumb_title')
    cashier / op umum
@endsection

@section('content')
<div class="vj-show">
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h3 class="card-title mb-0"><i class="fas fa-exchange-alt"></i> OP Umum (Pinbuk Bank → Cash)</h3>
                    <a href="{{ route('cashier.general-op.create') }}" class="vj-btn vj-btn-primary">
                        <i class="fas fa-plus"></i> Buat OP Umum
                    </a>
                </div>
                <div class="card-body">
                    <table id="general-op-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>No. OP SAP</th>
                                <th>Bank / Giro</th>
                                <th>Bilyet</th>
                                <th>PC</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
    @include('partials.vj-soft-ui-styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('scripts')
<script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script>
    $(function () {
        $('#general-op-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('cashier.general-op.data') }}',
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'doc_date' },
                { data: 'sap_doc_num' },
                { data: 'bank_giro', orderable: false },
                { data: 'bilyet', orderable: false },
                { data: 'profit_center', orderable: false, searchable: false },
                { data: 'amount', className: 'text-right' },
                { data: 'status', orderable: false },
                { data: 'action', orderable: false, searchable: false },
            ],
            order: [[1, 'desc']],
        });
    });
</script>
@endsection
