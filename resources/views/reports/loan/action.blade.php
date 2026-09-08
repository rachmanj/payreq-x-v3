@hasanyrole('superadmin|admin|cashier')
    <button type="button" class="vj-action-item vj-action-item-xs vj-action-edit" data-toggle="modal"
        data-target="#installment-edit-{{ $model->id }}">
        <i class="fas fa-edit"></i>
        <span>edit</span>
    </button>
@endhasanyrole

{{-- Modal edit --}}
<div class="modal fade" id="installment-edit-{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title"> Edit Installment / Paid date</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('reports.loan.update') }}" method="POST">
                @csrf

                <div class="modal-body">

                    <input type="hidden" name="installment_id" value="{{ $model->id }}">
                    <input type="hidden" name="form_type" value="reports">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="paid_date">Paid Date</label>
                                <input type="date" name="paid_date" id="paid_date" value="{{ $model->paid_date }}"
                                    class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="bilyet_no">Bilyet No</label>
                                <input type="text" name="bilyet_no" id="bilyet_no" value="{{ $model->bilyet_no }}"
                                    class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="bilyet_no">Account No</label>
                                <select name="account_id" class="form-control">
                                    <option value="">Select Account</option>
                                    @foreach (\App\Models\Account::where('type', 'bank')->get() as $account)
                                        <option value="{{ $account->id }}"
                                            {{ $account->id == $model->account_id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="vj-action-item vj-action-print" data-dismiss="modal"> Close</button>
                    <button type="submit" class="vj-btn vj-btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>

            </form>

        </div>
    </div>
</div>
