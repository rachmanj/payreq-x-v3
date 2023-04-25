<div class="col-12 col-sm-6 col-md-4">
    <div class="info-box mb-3">
      <span class="info-box-icon bg-success elevation-1"><i class="fas fa-folder-open"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Payreq Belum Realisasi</span>
        <h4><b>{{ $not_realization->count() }}</b> <small>Docs</small></h4>
      </div>
    </div>
  </div>
  
  <div class="col-12 col-sm-6 col-md-4">
    <div class="info-box mb-3">
      <span class="info-box-icon bg-success elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Payreq Belum Verifikasi</span>
        <h4><b>{{ $not_verify->count() }}</b> <small>Docs</small></h4>
      </div>
    </div>
  </div>
  
  <div class="col-12 col-sm-6 col-md-4">
    <div class="info-box mb-3">
      <span class="info-box-icon bg-success elevation-1"><i class="fas fa-dollar-sign"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Total Outstanding</span>
        <h4><small>IDR </small><b>{{ number_format($outstanding_payreq, 0) }}</b></h4>
      </div>
    </div>
  </div>