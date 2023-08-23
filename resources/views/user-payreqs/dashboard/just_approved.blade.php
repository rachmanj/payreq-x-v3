<div class="card card-info">
    <div class="card-header border-transaparent">
      <h3 class="card-title">Payreq Belum Outgoing</h3>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table m-0">
          <thead>
            <tr>
              <th>#</th>
              <th>PayreqNo</th>
              <th>Type</th>
              <th>Approve Date</th>
              <th class="text-center">IDR</th>
              <th class="text-center">Days</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @if ($just_approved->count() > 0)
              @foreach ($just_approved->get() as $payreq)
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td>{{ $payreq->payreq_num }}</td>
                  <td>{{ $payreq->payreq_type }}</td>
                  <td>{{ date('d-m-Y', strtotime($payreq->approve_date)) }}</td>
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