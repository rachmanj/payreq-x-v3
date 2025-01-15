@extends('templates.main')

@section('title_page')
    VAT Detail
@endsection

@section('breadcrumb_title')
    accounting / vat / detail
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Faktur Detail</h3>
                    <div class="float-right">
                        @if ($faktur->type === 'purchase' && !$faktur->attachment)
                            <button type="button" class="btn btn-sm btn-warning" data-toggle="modal"
                                data-target="#uploadModal"><i class="fas fa-upload"></i> Upload File</button>
                        @endif
                        @if ($faktur->type === 'sales' && !$faktur->doc_num)
                            <button type="button" class="btn btn-sm btn-warning" data-toggle="modal"
                                data-target="#docNumModal"><i class="fas fa-edit"></i> Update Doc Number</button>
                        @endif
                        @if ($faktur->attachment)
                            <a href="{{ $faktur->attachment }}" target="_blank" class="btn btn-sm btn-info"><i
                                    class="fas fa-paperclip"></i> View Attachment</a>
                        @endif
                        <a href="{{ route('accounting.vat.index', ['page' => 'search']) }}"
                            class="btn btn-sm btn-primary"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Type</th>
                                    <td>{{ $faktur->type }}</td>
                                </tr>
                                <tr>
                                    <th>Faktur Number</th>
                                    <td>{{ $faktur->faktur_no }}</td>
                                </tr>
                                <tr>
                                    <th>Faktur Date</th>
                                    <td>{{ $faktur->faktur_date ? date('d-M-Y', strtotime($faktur->faktur_date)) : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Invoice Number</th>
                                    <td>{{ $faktur->invoice_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Invoice Date</th>
                                    <td>{{ $faktur->invoice_date ? date('d-M-Y', strtotime($faktur->invoice_date)) : '-' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Customer/Supplier</th>
                                    <td>{{ $faktur->customer->name }}</td>
                                </tr>
                                <tr>
                                    <th>Document Number</th>
                                    <td>{{ $faktur->doc_num ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>DPP</th>
                                    <td>{{ number_format($faktur->dpp, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>PPN</th>
                                    <td>{{ number_format($faktur->ppn, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Create Date</th>
                                    <td>{{ date('d-M-Y', strtotime($faktur->create_date)) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Purchase Type -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload File</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('accounting.vat.update', $faktur->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="attachment">File Attachment</label>
                            <input type="file" class="form-control-file" id="attachment" name="attachment" required>
                            <small class="form-text text-muted">Upload file for faktur</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for Sales Type -->
    <div class="modal fade" id="docNumModal" tabindex="-1" role="dialog" aria-labelledby="docNumModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="docNumModalLabel">Update Document Number</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('accounting.vat.update', $faktur->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="doc_num">Document Number</label>
                            <input type="text" class="form-control" id="doc_num" name="doc_num"
                                value="{{ old('doc_num', $faktur->doc_num) }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('success') }}',
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}',
            });
        </script>
    @endif
@endsection
