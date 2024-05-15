<button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#vjdetail-edit-{{ $model->id }}">edit</button>

{{-- modal update --}}
<div class="modal fade" id="vjdetail-edit-{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <form action="{{ route('accounting.sap-sync.update_detail') }}" method="POST">
            @csrf
            <input type="hidden" name="vj_id" value="{{ $model->verification_journal_id }}">
            <input type="hidden" name="vj_detail_id" value="{{ $model->id }}">
                <div class="modal-body">

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Account Number</label>
                                <select name="account_code" class="form-control select2bs4">
                                    @foreach (\App\Models\Account::orderBy('account_number')->get() as $item)
                                      <option value="{{ $item->account_number }}" {{ old('account_code', $model->account_code) == $item->account_number  ? 'selected' : '' }} >{{ $item->account_number . ' - ' . $item->account_name }}</option>
                                    @endforeach
                                  </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Project</label>
                                <select name="project" class="form-control select2bs4">
                                    @foreach (\App\Models\Project::orderBy('code')->get() as $item)
                                      <option value="{{ $item->code }}" {{ old('project', $model->project) == $item->code  ? 'selected' : '' }} >{{ $item->code }}</option>
                                    @endforeach
                                  </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Cost Center</label>
                                <select name="cost_center" class="form-control select2bs4">
                                    @foreach (\App\Models\Department::orderBy('department_name')->get() as $item)
                                      <option value="{{ $item->sap_code }}" {{ old('cost_center', $model->cost_center) == $item->sap_code  ? 'selected' : '' }} >{{ $item->department_name . ' - ' . $item->sap_code }}</option>
                                    @endforeach
                                  </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                            <label>Description</label>
                            <input class="form-control" value="{{ $model->description }}" readonly>
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


