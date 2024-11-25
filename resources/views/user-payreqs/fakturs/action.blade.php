<form action="{{ route('user-payreqs.fakturs.destroy', $model->id) }}" class="d-inline" method="POST">
    @csrf @method('DELETE')
    @if ($model->faktur_no == null && $model->created_by == auth()->user()->id)
        <form action="{{ route('user-payreqs.fakturs.destroy', $model->id) }}" method="POST">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-xs btn-danger"
                onclick="return confirm('Are you sure to delete this record?')">delete</button>
        </form>
        <button type="button" class="btn btn-xs btn-primary" data-toggle="modal"
            data-target="#editinvoice{{ $model->id }}">
            edit
        </button>
    @endif
</form>

@can('update_faktur')
    <button type="button" class="btn btn-xs btn-primary" data-toggle="modal"
        data-target="#updateFaktur{{ $model->id }}">update
    </button>
@endcan

@if ($model->faktur_no != null)
    <a href="{{ $model->attachment }}" class="btn btn-xs btn-success" target="_blank" title="show faktur"><i
            class="fas fa-file-pdf"></i></a>
@endif

<!-- Modal Edit AR Invoice -->
<div class="modal fade" id="editinvoice{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit AR Invoice</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('user-payreqs.fakturs.update_arinvoice', $model->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="customer_id">Customer</label>
                                <select name="customer_id" id="customer_id" class="form-control select2bs4">
                                    <option value="">-- Select Customer --</option>
                                    @foreach (\App\Models\Customer::orderBy('name', 'asc')->get() as $customer)
                                        <option value="{{ $customer->id }}"
                                            {{ $customer->id == $model->customer_id ? 'selected' : '' }}>
                                            {{ $customer->name . ' - ' . $customer->project }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="invoice_no">Invoice No</label>
                                <input type="text" name="invoice_no" id="invoice_no" class="form-control"
                                    value="{{ old('invoice_no', $model->invoice_no) }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="invoice_date">Invoice Date</label>
                                <input type="date" name="invoice_date" id="invoice_date" class="form-control"
                                    value="{{ old('invoice_date', $model->invoice_date) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label for="kurs">Kurs <small>(optional)</small></label>
                                <input type="text" name="kurs" id="kurs" class="form-control"
                                    value="{{ old('kurs', $model->kurs) }}">
                            </div>
                        </div>
                        <div class="col-8">
                            <div class="form-group">
                                <label for="dpp">DPP <small>(IDR)</small></label>
                                <input type="text" name="dpp" id="dpp" class="form-control"
                                    value="{{ old('dpp', $model->dpp) }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control">{{ old('remarks', $model->remarks) }}</textarea>
                    </div>

                </div> <!-- /.modal-body -->
                <div class="modal-footer float-left">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
</div>

{{-- modal update faktur --}}
<div class="modal fade" id="updateFaktur{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Update Faktur</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('user-payreqs.fakturs.update_faktur') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">

                    <div class="row">
                        <div class="col-12">
                            <table class="table table-sm">
                                <tr>
                                    <td class="py-0">Invoice No</td>
                                    <td class="py-0">{{ $model->invoice_no }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0">Invoice Date</td>
                                    <td class="py-0">
                                        {{ \Carbon\Carbon::parse($model->invoice_date)->format('d-M-Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0">DPP
                                        <small>(kurs
                                            {{ $model->kurs ? number_format($model->kurs, 0, ',', '.') : '' }})</small>
                                    </td>
                                    <td class="py-0">Rp. {{ number_format($model->dpp, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="py-0">Remarks</td>
                                    <td class="py-0">{{ $model->remarks }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="faktur_no">Faktur No</label>
                                <input type="hidden" name="faktur_id" value="{{ $model->id }}">
                                <input type="text" name="faktur_no" id="faktur_no" class="form-control"
                                    value="{{ old('faktur_no', $model->faktur_no) }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="faktur_date">Faktur Date</label>
                                <input type="date" name="faktur_date" id="faktur_date" class="form-control"
                                    value="{{ old('faktur_date', $model->faktur_date) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="ppn">PPN <small>(IDR)</small></label>
                                <input type="text" name="ppn" id="ppn" class="form-control"
                                    value="{{ old('ppn', $model->ppn) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="attachment">Upload Faktur</label>
                                <input type="file" name="attachment" id="attachment" class="form-control">
                            </div>
                        </div>
                    </div>

                </div> <!-- /.modal-body -->
                <div class="modal-footer float-left">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"> Close</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div> <!-- /.modal-content -->
    </div> <!-- /.modal-dialog -->
</div>
