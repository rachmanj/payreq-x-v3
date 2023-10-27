<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</td>
                            <th>Desc</td>
                            <th>Account</th>
                            <th class="text-right">Amount (IDR)</th>
                            <th>actions</th>
                        </tr>
                    </thead>
                    @if ($realization_details->count() > 0) 
                        <tbody>
                            @foreach ($realization_details as $item)
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
                                    <td>
                                        <div class="form-group">
                                            <select id="account_id" name="account_id" class="form-control select2bs4">
                                                <option value="">-- Select Account --</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->account_number }}</option>
                                                @endforeach
                                            </select>
                                        </div> 
                                    </td>
                                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                                    <td>
                                        <form action="{{ route('user-payreqs.realizations.delete_detail', $item->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure you want delete this record?')">delete</button></form>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-right">Total</td>
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