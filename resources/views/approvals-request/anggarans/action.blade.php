@php
    // use App\Models\PeriodeAnggaran;
    $periode_ofrs = \App\Models\PeriodeAnggaran::orderBy('periode', 'asc')
                                        ->where('periode_type', 'ofr')
                                        ->where('project', auth()->user()->project)
                                        ->where('is_active', 1)
                                        ->get()
@endphp


<button type="button" class="btn btn-xs btn-warning" data-toggle="modal" data-target="#approvals-update-{{ $model->id }}">detail</button>

{{-- modal update --}}
<div class="modal fade" id="approvals-update-{{ $model->id }}">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Approval for RAB No. {{ $model->anggaran->nomor }}</h4>
            </div>

            <form action="{{ route('approvals.plan.update', $model->id) }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="document_type" value="rab">
                <div class="modal-body">

                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label for="requestor">Requestor</label>
                                <input type="text" id="requestor" class="form-control" value="{{ $model->anggaran->createdBy->name }}" readonly>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="text" id="amount" class="form-control" value="IDR {{ number_format($model->anggaran->amount, 2) }}" readonly>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="submit_at">Submitted at</label>
                                <input type="text" id="submit_at" class="form-control" value="{{ Carbon\Carbon::parse($model->anggaran->submit_at)->addHours(8)->format('d-M-Y H:i:s') }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="2" readonly>{{ $model->anggaran->description }}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="status">Approval Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">-- change status --</option>
                                    <option value="1">Approved</option>
                                    <option value="2">Revise</option>
                                    <option value="3">Reject</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="periode_ofr">Periode OFR</label>
                                <select name="periode_ofr" id="periode_ofr" class="form-control">
                                    @foreach ($periode_ofrs as $periode_ofr)
                    
                                        {{-- {{ $date = \Carbon\Carbon::parse($periode_anggaran->periode) }}
                                        {{ $formattedDate = $date->format('F Y') }} --}}
                                    
                                    <option value="{{ $periode_ofr->periode }}">{{ \Carbon\Carbon::parse($periode_ofr->periode)->format('F Y') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div id="remarks" class="form-group">
                                <label for="remarks">Note</label>
                                <textarea name="remarks" id="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
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


