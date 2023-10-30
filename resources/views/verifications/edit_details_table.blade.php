<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                {{-- <h3 class="card-title">Details</h3> --}}
                {{-- button back --}}
                <a href="{{ route('verifications.index') }}" class="btn btn-sm btn-success float-right"><i class="fas fa-arrow-left"></i> Back</a>
                <button type="submit" form="save_verification" action="{{ route('verifications.index') }}" class="btn btn-sm btn-primary" ><i class="fas fa-save"></i> SAVE</button>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</td>
                            <th>Desc</td>
                            <th>Current Account</td>
                            <th>New Account</th>
                            <th class="text-right">Amount (IDR)</th>
                        </tr>
                    </thead>
                    @if ($realization_details->count() > 0) 
                        <tbody>
                            <form action="{{ route('verifications.save') }}" id="save_verification" method="POST">
                                @csrf
                            <input type="hidden" name="verification_id" value="{{ $verification->id }}">
                            <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                            @foreach ($realization_details as $key => $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->description }} 
                                        @if ($item->unit_no != null)
                                            <br/>
                                            @if ($item->type === 'fuel')
                                                <small>Unit No: {{ $item->unit_no }}, {{ $item->type }} {{ $item->qty }} {{ $item->uom }}. HM: {{ $item->km_position }}</small>
                                            @else
                                                <small>{{ $item->type }}, HM: {{ $item->km_position }}</small>
                                            @endif 
                                        @endif
                                    </td>
                                    <td>{{ $item->account_id ? $item->account->account_number : '-' }}</td>
                                    <td>
                                        <div class="form-group">
                                            <input type="hidden" value="{{ $item->id }}" name="realization_details[{{ $key }}][id]">
                                            <input type="text" id="account_number_{{ $item->id }}" name="realization_details[{{ $key }}][account_number]">
                                            <input type="text" id="account_name_{{ $item->id }}" style="border: none" disabled>
                                        </div> 
                                    </td>
                                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            </form>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right">TOTAL</td>
                                <td class="text-right"><b>{{ number_format($realization_details->sum('amount'), 2) }}</b></td>
                            </tr>
                        </tfoot>
                    @else
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center">No Data Found</td>
                            </tr>
                        </tbody>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>