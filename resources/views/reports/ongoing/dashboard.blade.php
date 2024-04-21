@extends('templates.main')

@section('title_page')
  Ongoing Payreq Dashboard
@endsection

@section('breadcrumb_title')
    reports / ongoing / dashboard
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card card-info">
      <div class="card-header">
        <h3 class="card-title">Rekaps</h3>
        <a href="{{ route('reports.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-arrow-left"></i> Back to Index</a>
      </div>
      <div class="card-body">
        {{-- <dl class="row">
          <dt class="col-sm-4">Saldo PC Payreq System</dt>
          <dd class="col-sm-8">: IDR {{ $dashboard_data['saldo_pc_payreq_system'] }}</dd>
          <dt class="col-sm-4">Payreq belum realisasi</dt>
            <dd class="col-sm-8">: IDR {{ $dashboard_data['payreq_belum_realisasi'] }}</dd>
          <dt class="col-sm-4">Payreq belum verifikasi</dt>
            <dd class="col-sm-8">: IDR {{ $dashboard_data['payreq_belum_verifikasi'] }}</dd>
          <dt class="col-sm-4">Cek balance PC SAP</dt>
            <dd class="col-sm-8">: IDR {{ $dashboard_data['cek_balance_pc_sap'] }}</dd>
        </dl> --}}
        <table class="table table-striped">
          <tbody>
            <tr>
              <td>A. Saldo PC Payreq System</td>
              <td></td>
                <td class="text-right"><strong>Rp. {{ $dashboard_data['saldo_pc_payreq_system'] }}</strong></td>
              <td></td>
            </tr>
            <tr>
              <td>B. Payreq belum realisasi</td>
              <td class="text-right">Rp. {{ $dashboard_data['payreq_belum_realisasi_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>C. Realisasi belum verifikasi</td>
              <td class="text-right">Rp. {{ $dashboard_data['realisasi_belum_verifikasi_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>D. Verifikasi belum posted</td>
              <td class="text-right">Rp. {{ $dashboard_data['verifikasi_belum_posted_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>E. Variance Realisasi belum incoming</td>
              <td class="text-right">Rp. {{ $dashboard_data['variance_realisasi_belum_incoming_amount'] }}</td>
              <td></td>
            </tr>
            <tr>
              <td>F .Variance Realisasi belum outgoing</td>
                <td class="text-right" style="color: red;">(Rp. {{ $dashboard_data['variance_realisasi_belum_outgoing_amount'] }})</td>
              <td></td>
            </tr>
            <tr>
              <td>G. Total Advance Employee (B + C + D + E - F)</td>
              <td></td>
                <td class="text-right"><strong>Rp. {{ $dashboard_data['total_advance_employee'] }}</strong></td>
              <td></td>
            </tr>
            <tr>
              <td>H. Cek balance PC SAP (A + G)</td>
              <td></td>
                <td class="text-right"><strong>Rp. {{ $dashboard_data['cek_balance_pc_sap'] }}</strong></td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div> 
  </div>
</div>

{{-- acordion --}}
<div class="row">
  <div class="col-12">
    <div class="card card-info">
      <div class="card-header">
        <h3 class="card-title">Ongoing Payreq by user</h3>
      </div>

      <div class="card-body">

        <div id="accordion">

          @foreach ($dashboard_data['ongoing_documents_by_user'] as $user)
            @if($user->display)
          <div class="card">
            <div class="card-header">
              <h4 class="card-title w-100">
                <a class="d-block w-100" data-toggle="collapse" href="#collapse{{ $user->index }}">
                  {{ $user->name }} <span class="float-right">IDR {{ $user->dana_belum_diselesaikan }}</span>
                </a>
              </h4>
            </div>
            <div id="collapse{{ $user->index }}" class="collapse" data-parent="#accordion">
              <div class="card-body">
                <ol>
                  @if ($user['payreq_belum_realisasi_amount'] !== 0 )
                    <li class="col-sm-12"><strong>Payreq belum realisasi total = Rp. {{ $user['payreq_belum_realisasi_amount'] }}</strong></li>
                  <ol>
                    @foreach ($user['payreq_belum_realisasi_list'] as $item)
                        <li class="col-sm-12">Payreq No.{{ $item->payreq_nomor }} | Paid {{ date('d-M-Y', strtotime($item->paid_date)) }} = Rp. {{ number_format($item->total_amount, 2) }}</li>  
                    @endforeach
                  </ol>
                  @endif
                  @if ($user['realisasi_belum_verifikasi_amount'] !== 0 )
                    <li class="col-sm-12"><strong>Realisasi belum verifikasi total = Rp. {{ $user['realisasi_belum_verifikasi_amount'] }}</strong></li>
                  <ol>
                    @foreach ($user['realisasi_belum_verifikasi_list'] as $item)
                      <li class="col-sm-12">Realisasi No.{{ $item->realization_nomor }} = Rp. {{ number_format($item->total_amount, 2) }}</li>  
                    @endforeach
                  </ol>
                  @endif
                  @if ($user['variance_realisasi_belum_incoming_amount'] !== 0 )
                    <li class="col-sm-12"><strong>Variance Realisasi belum incoming total = Rp. {{ $user['variance_realisasi_belum_incoming_amount'] }}</strong></li>
                  <ol>
                    @foreach ($user['variance_realisasi_belum_incoming_list'] as $item)
                      <li class="col-sm-12">Realisasi No.{{ $item->realization_nomor }} = Rp. {{ number_format($item->amount, 2) }}</li>  
                    @endforeach
                  </ol>
                  @endif
                  @if ($user['variance_realisasi_belum_outgoing_amount'] !== 0 )
                    <li class="col-sm-12"><strong>Variance Realisasi belum outgoing total = Rp. {{ $user['variance_realisasi_belum_outgoing_amount'] }}</strong></li>
                  <ol>
                    @foreach ($user['variance_realisasi_belum_outgoing_list'] as $item)
                      <li class="col-sm-12">Realisasi No.{{ $item->nomor }} = Rp. {{ number_format($item->amount, 2) }}</li>  
                    @endforeach
                  </ol>
                  @endif
                </ol>
              </div>
              
            </div>
          </div>
            @endif
          @endforeach

        </div> {{-- accordion --}}
      </div>

    </div> {{-- card --}}
  </div>
</div>

@endsection