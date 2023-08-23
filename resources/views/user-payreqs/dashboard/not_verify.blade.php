<div class="card card-info">
    <div class="card-header border-transaparent">
      <h3 class="card-title">Dokumen Belum Verifikasi</h3>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table m-0">
          <thead>
            <tr>
              <th>#</th>
              <th>PayreqNo</th>
              <th>Outgoing</th>
              <th>RlzNo</th>
              <th>RlzDate</th>
              <th class="text-right">IDR</th>
              <th class="text-right">Days</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @if ($not_verify->count() > 0)
              @foreach ($not_verify->get() as $payreq)
                <tr class="{{ $payreq->days > 3 ? 'text-red' : '' }}">
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $payreq->payreq_num }}</td>
                  <td>{{ date('d-m-Y', strtotime($payreq->outgoing_date)) }}</td>
                  <td>{{ $payreq->realization_num }}</td>
                  <td>{{ date('d-m-Y', strtotime($payreq->realization_date)) }}</td>
                  <td class="text-right">{{ number_format($payreq->payreq_idr, 0) }}</td>
                  <td class="text-right">{{ $payreq->days }}</td>
                  <td><a href="{{ route('dashboard.show', $payreq->id) }}" class="btn btn-xs btn-info"><i class="fas fa-search"></i></a></td>
                </tr>
              @endforeach
            @else
              <tr>
                <td colspan="5" class="text-center">Tidak ada data</td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>