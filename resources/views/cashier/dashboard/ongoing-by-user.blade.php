<div class="col-12">
    <div class="card card-info">
      <div class="card-header">
        <h3 class="card-title">Ongoing Payreq by user</h3>
      </div>

      <div class="card-body">

        <div id="accordion">

          @foreach ($dashboard_report['ongoing_documents_by_user'] as $user)
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
                      <li class="col-sm-12">Realisasi No.{{ $item->realization_nomor }} | Approved at {{ date('d-M-Y', strtotime($item->approved_at)) }} = Rp. {{ number_format($item->total_amount, 2) }}</li>  
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