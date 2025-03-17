@extends('templates.main')

@section('title_page')
    Approvals Request
@endsection

@section('breadcrumb_title')
    approvals
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <div class="h3 card-title">
                        <a href="{{ route('approvals.request.payreqs.index') }}">Payment Request @if ($document_count['payreq'] > 0)
                                <span class="badge badge-danger">{{ $document_count['payreq'] }}</span>
                            @endif
                        </a>
                        |
                        <b>Realizations</b>
                        @if ($document_count['realization'] > 0)
                            <span class="badge badge-danger">{{ $document_count['realization'] }}</span>
                        @endif |
                        <a href="{{ route('approvals.request.anggarans.index') }}">RABs @if ($document_count['rab'] > 0)
                                <span class="badge badge-danger">{{ $document_count['rab'] }}</span>
                            @endif
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table id="mypayreqs" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>RealzNo</th>
                                <th>PayreqNo</th>
                                <th>Requestor</th>
                                <th>Submit at</th>
                                <th>IDR</th>
                                <th>Days</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <!-- /.card-body -->
            </div>
            <!-- /.card -->


        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('adminlte/plugins/datatables/css/datatables.min.css') }}" />
@endsection

@section('scripts')
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables/datatables.min.js') }}"></script>

    <script>
        $(function() {
            // Initialize DataTable
            var table = $("#mypayreqs").DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('approvals.request.realizations.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nomor'
                    },
                    {
                        data: 'payreq_no'
                    },
                    {
                        data: 'requestor'
                    },
                    {
                        data: 'submit_at'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'days'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                fixedHeader: true,
                columnDefs: [{
                    "targets": [5, 6],
                    "className": "text-right"
                }, ]
            });

            // Handle AJAX form submission for approval forms
            $(document).on('submit', '.approval-form', function(e) {
                e.preventDefault();

                var form = $(this);
                var url = form.attr('action');
                var modal = form.closest('.modal');

                $.ajax({
                    type: "POST",
                    url: url,
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        // Close the modal
                        modal.modal('hide');

                        // Show success message with Toastr
                        toastr.success(response.message);

                        // Refresh the DataTable
                        table.ajax.reload();
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        var errorMessage = xhr.responseJSON ? xhr.responseJSON.message :
                            'An error occurred';
                        toastr.error(errorMessage);
                    }
                });
            });
        });
    </script>
    <script>
        $(function() {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
        })
    </script>
@endsection
