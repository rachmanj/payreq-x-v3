<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h4 class="card-title">Payreq Remarks</h4>
                <a href="{{ route('user-payreqs.index') }}" class="btn btn-sm btn-info float-right"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <input type="text" name="remarks" value="{{ old('remarks', $payreq->remarks) }}" class="form-control">
                        </div>
                    </div>
                </div>
                @can('rab_select')
                <div class="row">
                    <div class="col-12">
                        <div class="input-group input-group-xs">
                            {{-- <label for="anggaran">RAB</label> --}}
                            <select name="rab_id" class="form-control select2bs4">
                                <option value="">-- Select RAB --</option>
                                @foreach ($rabs as $rab)
                                  <option value="{{ $rab->id }}" {{ $payreq->rab_id == $rab->id ? 'selected' : '' }}>{{ $rab->rab_no ? $rab->rab_no : $rab->nomor }} | {{ $rab->rab_project }} | {{ $rab->description }}</option>
                                @endforeach
                            </select>
                            <span class="input-group-append">
                                <button type="button" id="update_rab" class="btn btn-info btn-xs btn-flat">update</button>
                            </span>
                        </div>
                    </div>
                </div>
                @endcan
            </div>
            <div class="card-header">
                <h4 class="card-title">Form</h4>
                <form action="{{ route('user-payreqs.reimburse.submit_payreq') }}" method="POST">
                    @csrf
                    @if ($realization->realizationDetails->count() > 0)
                        <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                        <button type="submit" class="btn btn-sm btn-warning float-right mx-2" onclick="return confirm('Are you sure you want to submit this realization?')"><b>Submit Payreq</b></button>
                    @endif
                </form>
            </div>
            
            <form action="{{ route('user-payreqs.reimburse.store_detail') }}" method="POST">
                @csrf
                <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                <div class="card-body">
                    
                    <div class="row">
                        <div class="col-8">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <input type="text" name="description" value="{{ old('description') }}" id="description" class="form-control @error('description') is-invalid @enderror">
                                @error('description')
                                <div class="invalid-feedback">
                                {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="text" name="amount" value="{{ old('amount') }}" id="amount" class="form-control @error('amount') is-invalid @enderror" autocomplete="off">
                                @error('amount')
                                <div class="invalid-feedback">
                                {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                          
                        <div class="col-4"> 
                            <div class="form-group">
                                <label for="unit_no">Unit No</label>
                                <select id="unit_no" name="unit_no" class="form-control select2bs4">
                                    <option value="">-- select unit no --</option>
                                    @foreach ($equipments as $item)
                                        <option value="{{ $item->unit_code }}">{{ $item->unit_code }} - {{ $item->project }} - {{ $item->plant_group }} - {{ $item->nomor_polisi }}</option>
                                    @endforeach
                                </select>
                            </div> 
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="nopol">No Polisi <small>(optional)</small></label>
                                <input type="text" name="nopol" value="{{ old('nopol') }}" id="nopol" class="form-control">
                            </div>
                        </div>

                        <div class="col-1">
                            <div class="form-group">
                                <label for="qty">Qty</label>
                                <input id="qty" name="qty" class="form-control">
                            </div> 
                        </div>
                        <div class="col-1">
                            <div class="form-group">
                                <label for="km_position">HM</label>
                                <input id="km_position" name="km_position" class="form-control">
                            </div> 
                        </div>

                        <div class="col-2">
                            <div class="form-group">
                                <label for="type">Type</label>
                                <select id="type" name="type" class="form-control select2bs4">
                                    <option value="">-- type --</option>
                                    <option value="fuel">Fuel</option>
                                    <option value="service">Service</option>
                                    <option value="tax">STNK / Tax</option>
                                    <option value="other">Others</option>
                                </select>
                            </div> 
                        </div>
                        
                        <div class="col-2">
                            <div class="form-group">
                                <label for="uom">UOM</label>
                                <select id="uom" name="uom" class="form-control select2bs4">
                                    <option value="">-- uom --</option>
                                    <option value="liter">liter</option>
                                    <option value="each">Each</option>
                                </select>
                            </div> 
                        </div>
                        
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-sm btn-success btn-block"><i class="fas fa-save"></i>  ADD DETAIL</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
{{-- when update_rab button clicked then send api request with method post --}}
<script>
    $(document).ready(function() {
        $('#update_rab').click(function() {
            var rab_id = $('select[name="rab_id"]').val();
            var remarks = $('input[name="remarks"]').val();
            var payreq_id = '{{ $payreq->id }}';
            $.ajax({
                url: '{{ route('user-payreqs.reimburse.update_rab') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    rab_id: rab_id,
                    remarks: remarks,
                    payreq_id: payreq_id,
                },
                success: function(response) {
                    if (response.status == 'success') {
                        return true;
                    } else {
                        return false;
                    }
                }
            });
        });
    });
</script>
@endsection