@extends('templates.main')

@section('title_page')
  Equipments
@endsection

@section('breadcrumb_title')
    equipments
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card racd-info">
      <div class="card-header">
        <h3 class="card-title">Equipment Synchronization</h3>
      </div>
      <div class="form-horizontal">
        <div class="card-body">
            <p style="color: blue;">This action is to synchronize Equipments belongs to ARK-Fleet server with this Payreq-X.</p>
          @if (Session::has('error'))
            <div class="alert alert-danger alert-dismissible">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
              <h5><i class="icon fas fa-ban"></i> Error!</h5>
              {{ Session::get('error') }}
            </div>
          @endif
          <div class="form-group row">
            <label class="col-sm-4 col-form-label">Equipments ARK-Fleet count: </label>
            <div class="col-sm-6">
              <input type="text" class="form-control {{ $api_count == 0 ? 'border-danger' : '' }}" value="{{ $api_count }}" readonly>
              @if ($api_count == 0)
                <small class="text-danger">Unable to fetch count from ARK-Fleet API. Please check the error message above.</small>
              @endif
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-4 col-form-label">Equipments local count: </label>
            <div class="col-sm-6">
              <input type="text" class="form-control" value="{{ $local_count }}" readonly>
            </div>
          </div>
        </div>
        <div class="card-footer text-center">
          <button type="button" class="btn btn-info" id="sync-btn" style="width: 60%">Synchronize</button>
        </div>
      </div>
    </div> 
  </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const syncBtn = document.getElementById('sync-btn');
        const syncUrl = '{{ route('equipments.sync.sync_equipments') }}';
        const apiCount = {{ $api_count }};
        const localCount = {{ $local_count }};

        syncBtn.addEventListener('click', function() {
            const summaryHtml = `
                <div class="text-left">
                    <p>You are about to synchronize equipment data from ARK-Fleet server.</p>
                    <ul class="pl-3 mb-0">
                        <li>ARK-Fleet active equipment count: <strong>${apiCount}</strong></li>
                        <li>Current local equipment count: <strong>${localCount}</strong></li>
                        <li class="mt-2"><strong>Warning:</strong> This action will replace all existing local equipment data.</li>
                    </ul>
                </div>
            `;

            Swal.fire({
                title: 'Synchronize Equipment?',
                html: summaryHtml,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, synchronize',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#17a2b8',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Synchronizing...',
                        html: 'Please wait while we synchronize equipment data from ARK-Fleet.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                            window.location.href = syncUrl;
                        }
                    });
                }
            });
        });
    });
</script>
@endpush

@endsection