<form action="{{ route('cashier.pcbc.update', $pcbc->id) }}" method="POST">
    @csrf @method('PUT')
<div class="row">
    <div class="col-12">
    
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">PCBC No.{{ $pcbc->nomor }}</h3>
                <a href="{{ route('cashier.pcbc.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back</a>
                <button type="submit" class="btn btn-sm btn-warning float-right mx-2" style="width: 100px;"><i class="fas fa-save"></i> Save</button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-4">
                        <div class="form-group text-center">
                            <label for="date">Date</label>
                            <input type="date" class="form-control text-center" name="date" value="{{ old('date', $pcbc->date) }}">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group text-center">
                            <label for="checked_by">Checked by</label>
                            <input type="text" class="form-control text-center" name="checked_by" value="{{ old('checked_by', $pcbc->checked_by) }}"  placeholder="Checked By">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group text-center">
                            <label for="approved_by">Approved by</label>
                            <input type="text" class="form-control text-center" name="approved_by" value="{{ old('checked_by', $pcbc->approved_by) }}" placeholder="approved by">
                        </div>
                    </div>
                </div>
                <hr> {{-- divider --}}
                <div class="row">
                    <div class="col-4">
                        <div class="form-group text-center">
                            <label for="app_balance">Saldo PC sesuai Aplikasi</label>
                            <input type="text" class="form-control text-center" id="app_balance" value="{{ number_format($pcbc->app_balance, 2) }}" disabled>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="form-group text-center">
                            <label for="app_balance">Saldo Fisik</label>
                            <input type="text" class="form-control text-center" id="fisik_total" disabled>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="form-group text-center">
                            <label for="app_balance">Variance Fisik</label>
                            <input type="text" class="form-control text-center" id="variance" disabled>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <div class="form-group text-center">
                            <label for="sap_balance">Saldo PC sesuai SAP</label>
                            <input type="text" class="form-control text-center" name="sap_balance" value="{{ $pcbc->sap_balance }}" id="sap_balance" onchange="calculateVarianceAplikasi()">
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="form-group text-center">
                            <label for="app_balance">Saldo PC sesuai Aplikasi</label>
                            <input type="text" class="form-control text-center" id="app_balance" value="{{ number_format($pcbc->app_balance, 2) }}" disabled>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="form-group text-center">
                            <label for="variance_aplikasi">Variance Aplikasi</label>
                            <input type="text" class="form-control text-center" id="variance_aplikasi" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-6">
        {{-- UANG KERTAS --}}
        @include('cashier.pcbc.kertas')
    </div>
    <div class="col-6">
        {{-- COINS --}}
        @include('cashier.pcbc.coin')
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-body">
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" cols="30" rows="3" class="form-control" placeholder="Penjelasan seperlunya jika terjadi selisih">{{ $pcbc->remarks }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>
</form>