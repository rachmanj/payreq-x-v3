@extends('templates.main')

@section('title_page')
    Creditors Management
@endsection

@section('breadcrumb_title')
    creditors
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Creditors Management</h3>
                    <a href="{{ route('admin.creditors.create') }}" class="btn btn-sm btn-primary float-right">
                        <i class="fas fa-plus"></i> Add New Creditor
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Manage creditors and link them to SAP Business Partners (Suppliers/Vendors) for AP Invoice creation.
                        <br><small>Note: Creditors must be linked to SAP Business Partners before creating AP Invoices for loan installments.</small>
                    </div>

                    <table id="creditors-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Creditor Name</th>
                                <th>SAP Code</th>
                                <th>SAP Name</th>
                                <th>SAP Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function() {
            var table = $('#creditors-table').DataTable({
                processing: true,
                serverSide: false,
                responsive: true,
                ajax: {
                    url: "{{ route('admin.creditors.data') }}",
                    type: "GET"
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'sap_code', name: 'sap_code', orderable: false },
                    { data: 'sap_name', name: 'sap_name' },
                    { data: 'sap_status', name: 'sap_status', orderable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[1, 'asc']]
            });
        });

        function deleteCreditor(id) {
            if (confirm('Are you sure you want to delete this creditor?')) {
                $.ajax({
                    url: "{{ url('admin/creditors') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            $('#creditors-table').DataTable().ajax.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON;
                        alert(response.message || 'Error deleting creditor');
                    }
                });
            }
        }
    </script>
@endsection
