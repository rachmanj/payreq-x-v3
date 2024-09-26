@if($model->project == auth()->user()->project)
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-xs btn-success" data-toggle="modal" data-target="#bilyet-release-{{ $model->id }}">release</button>
        @can('delete_bilyet')
        <form action="{{ route('cashier.bilyets.destroy', $model->id) }}" method="POST" style="display:inline;">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-xs btn-danger ml-2" onclick="return confirm('Are you sure you want delete this record?')">delete</button>
        </form>
        @endcan
    </div>
@endif

{{-- modal receive --}}
<div class="modal fade" id="bilyet-release-{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Release Data for {{ $model->type }} no {{ $model->prefix . $model->nomor }}</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="{{ route('cashier.bilyets.update', $model->id) }}" method="POST">
                @csrf @method('PUT')

                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="bilyet_date">Bilyet Date</label>
                                <input type="hidden" name="from_page" value="index">
                                <input type="date" name="bilyet_date" id="bilyet_date" class="form-control" value="{{ old('bilyet_date', $model->bilyet_date) }}">
                            </div>
                            <div class="form-group">
                                <label for="cair_date">Cair Date</label>
                                <input type="date" name="cair_date" id="cair_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="remarks">Purpose</label>
                                <textarea type="text" name="remarks" id="remarks" class="form-control">{{ old('remarks') }}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="text" name="amount" id="amount" class="form-control" value="{{ old('amount') }}">
                            </div>
                            <div class="form-group">
                                <label for="is_void">Is VOID?</label>
                                <select name="is_void" class="form-control">
                                    <option value="" {{ $model->status !== 'void' ? 'selected' : '' }}>NO</option>
                                    <option value="void" {{ $model->status == 'void' ? 'selected' : '' }}>YES</option>
                                </select>
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