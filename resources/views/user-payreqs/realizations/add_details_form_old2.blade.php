<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h4 class="card-title">Form</h4>
                <a href="{{ route('user-payreqs.realizations.index') }}" class="btn btn-sm btn-info float-right"><i class="fas fa-arrow-left"></i> Back</a>
                <form action="{{ route('user-payreqs.realizations.submit_realization') }}" method="POST">
                    @csrf
                    @if ($realization_details->count() > 0)
                        <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                        <button type="submit" class="btn btn-sm btn-warning float-right mx-2" onclick="return confirm('Are you sure you want to submit this realization?')">Submit Realization</button>
                    @endif
                </form>
            </div>
            <form action="{{ route('user-payreqs.realizations.store_detail') }}" method="POST">
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
                                </select>
                            </div> 
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="type">Type</label>
                                <select id="type" name="type" class="form-control select2bs4">
                                    <option value="">-- type --</option>
                                    <option value="fuel">Fuel</option>
                                    <option value="service">Service</option>
                                </select>
                            </div> 
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="qty">Qty</label>
                                <input id="qty" name="qty" class="form-control">
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
                        <div class="col-2">
                            <div class="form-group">
                                <label for="km_position">HM</label>
                                <input id="km_position" name="km_position" class="form-control">
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