@can('see_team')
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header py-1">
                <h3 class="card-title">Your Team Ongoings</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    {{-- <thead>
                        <tr>
                            <td colspan="2">Desc</td>
                            <td>Status</td>
                            <td class="text-right">IDR</td>
                            <td class="text-right">Days</td>
                        </tr>
                    </thead> --}}
                    <tbody>
                        @foreach ($your_team as $member)
                        <tr>
                            <th class="pb-0"><small><b>{{ $member['name'] }}</b></small></th>
                            @foreach ($member['ongoings'] as $payreq)
                                <tr>
                                    <td class="pt-0" colspan="2"><small>{{ $payreq['description'] }}</small></td>
                                    <td class="pt-0"><small>[ {{ $payreq['status'] }} ]</small></td>
                                    <td class="pt-0 text-right"><small>Rp.{{ $payreq['amount'] }}</small></td>
                                    <td class="pt-0 text-right" style="width: 10%"><small>{{ $payreq['days'] }} days</small></td>
                                </tr>
                            @endforeach
                        </tr>    
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endcan