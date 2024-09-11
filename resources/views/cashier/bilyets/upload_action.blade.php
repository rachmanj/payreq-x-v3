@if($model->project == auth()->user()->project)
<button type="button" class="btn btn-xs btn-success" data-toggle="modal" data-target="#bilyet-release-{{ $model->id }}">edit</button>
<a role="button" href="{{ route('cashier.bilyet-temps.destroy', $model->id) }}" class="btn btn-xs btn-danger d-line" onclick="return confirm('Are you sure you want to delete this record?')">delete</a>
@endif

{{-- modal receive --}}
<div class="modal fade" id="bilyet-release-{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Data for {{ $model->type }} no {{ $model->prefix . $model->nomor }}</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="{{ route('cashier.bilyets.update', $model->id) }}" method="POST">
                @csrf @method('PUT')

                <div class="modal-body">
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label for="prefix">Prefix</label>
                                <input type="text" name="prefix" id="prefix" class="form-control" value="{{ old('prefix', $model->prefix) }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="nomor">Nomor</label>
                                <input type="text" name="nomor" id="nomor" class="form-control" value="{{ old('nomor', $model->nomor) }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="type">Type</label>
                                <select name="type" id="type" class="form-control">
                                    <option value="cek" {{ old('type', $model->type) == 'cek' ? 'selected' : '' }}>Cek</option>
                                    <option value="bg" {{ old('type', $model->type) == 'bg' ? 'selected' : '' }}>BG</option>
                                    <option value="loa" {{ old('type', $model->type) == 'loa' ? 'selected' : '' }}>LOA</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="bilyet_date">Bilyet Date</label>
                                <input type="hidden" name="status" value="release">
                                <input type="date" name="bilyet_date" id="bilyet_date" class="form-control" value="{{ old('bilyet_date', $model->bilyet_date) }}">
                            </div>
                            <div class="form-group">
                                <label for="cair_date">Cair Date</label>
                                <input type="date" name="cair_date" id="cair_date" class="form-control" value="{{ old('cair_date', $model->cair_date) }}">
                            </div>
                            <div class="form-group">
                                <label for="remarks">Purpose</label>
                                <textarea type="text" name="remarks" id="remarks" class="form-control">{{ old('remarks', $model->remarks) }}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="text" name="amount" id="amount" class="form-control" value="{{ old('amount', $model->amount) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>