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

          <div id="fetch-alert" class="d-none"></div>

          @if (Session::has('success'))
            <div class="alert alert-success alert-dismissible">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
              <h5><i class="icon fas fa-check"></i> Success!</h5>
              {{ Session::get('success') }}
            </div>
          @endif

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
              <div class="input-group">
                <input type="text" id="api-count-input" class="form-control" value="" placeholder="Click Fetch to load" readonly>
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-primary" id="fetch-btn" title="Fetch count from ARK-Fleet server">
                    <i class="fas fa-sync-alt" id="fetch-icon"></i> Fetch
                  </button>
                </div>
              </div>
              <small id="api-count-hint" class="text-muted">Use Fetch to load the ARK-Fleet count without reloading this page.</small>
            </div>
          </div>

          <div class="form-group row">
            <label class="col-sm-4 col-form-label">Equipments local count: </label>
            <div class="col-sm-6">
              <input type="text" class="form-control" value="{{ $local_count }}" readonly>
            </div>
          </div>

          <div class="form-group row">
            <label class="col-sm-4 col-form-label">Connection diagnostics: </label>
            <div class="col-sm-6">
              <div class="card card-outline card-secondary mb-0">
                <div class="card-header py-2">
                  <h3 class="card-title text-sm">ARK-Fleet API debug info</h3>
                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                      <i class="fas fa-minus"></i>
                    </button>
                  </div>
                </div>
                <div class="card-body py-2">
                  <dl class="row mb-2 text-sm">
                    <dt class="col-sm-4">Configured URL</dt>
                    <dd class="col-sm-8"><code id="configured-url">{{ $configured_url ?: '(not set)' }}</code></dd>
                    <dt class="col-sm-4">Request URL</dt>
                    <dd class="col-sm-8"><code id="request-url">{{ $request_url ?: '(not available)' }}</code></dd>
                    <dt class="col-sm-4">Last fetch</dt>
                    <dd class="col-sm-8" id="last-fetch-status">Not fetched yet</dd>
                  </dl>
                  <pre id="debug-output" class="bg-light p-2 mb-0 text-sm" style="max-height: 260px; overflow: auto;">Click Fetch to run a connection test and see detailed diagnostics.</pre>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer text-center">
          <button type="button" class="btn btn-info" id="sync-btn" style="width: 60%" disabled>Synchronize</button>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const syncBtn = document.getElementById('sync-btn');
        const fetchBtn = document.getElementById('fetch-btn');
        const fetchIcon = document.getElementById('fetch-icon');
        const apiCountInput = document.getElementById('api-count-input');
        const apiCountHint = document.getElementById('api-count-hint');
        const fetchAlert = document.getElementById('fetch-alert');
        const debugOutput = document.getElementById('debug-output');
        const lastFetchStatus = document.getElementById('last-fetch-status');
        const syncUrl = '{{ route('equipments.sync.sync_equipments') }}';
        const fetchUrl = '{{ route('equipments.sync.fetch_count') }}';
        const localCount = {{ $local_count }};
        let apiCount = null;

        function setFetching(isFetching) {
            fetchBtn.disabled = isFetching;
            fetchIcon.classList.toggle('fa-spin', isFetching);
        }

        function showFetchAlert(type, message) {
            fetchAlert.className = 'alert alert-' + type + ' alert-dismissible';
            fetchAlert.innerHTML = `
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-${type === 'success' ? 'check' : 'ban'}"></i> ${type === 'success' ? 'Success' : 'Error'}!</h5>
                ${message}
            `;
            fetchAlert.classList.remove('d-none');
        }

        function updateCountUi(success, count) {
            apiCount = success ? count : null;
            apiCountInput.value = success ? String(count) : '';
            apiCountInput.classList.toggle('border-danger', !success);
            apiCountInput.classList.toggle('border-success', success && count > 0);
            apiCountHint.className = success ? 'text-success' : 'text-danger';
            apiCountHint.textContent = success
                ? 'ARK-Fleet count loaded successfully.'
                : 'Unable to fetch count from ARK-Fleet API. See diagnostics below.';
            syncBtn.disabled = !success || count <= 0;
        }

        function renderDebug(debug, message) {
            const payload = {
                message: message,
                debug: debug,
            };
            debugOutput.textContent = JSON.stringify(payload, null, 2);
            lastFetchStatus.textContent = new Date().toLocaleString();
        }

        function fetchArkFleetCount() {
            setFetching(true);
            fetchAlert.classList.add('d-none');

            fetch(fetchUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(async (response) => {
                    const data = await response.json();
                    renderDebug(data.debug || {}, data.message || 'No message returned.');

                    if (data.success) {
                        updateCountUi(true, data.count);
                        showFetchAlert('success', data.message);
                    } else {
                        updateCountUi(false, 0);
                        showFetchAlert('danger', data.message);
                    }
                })
                .catch((error) => {
                    const debug = {
                        client_error: error.message,
                        fetch_url: fetchUrl,
                    };
                    renderDebug(debug, 'Browser failed to call the fetch endpoint.');
                    updateCountUi(false, 0);
                    showFetchAlert('danger', 'Failed to call fetch endpoint: ' + error.message);
                })
                .finally(() => {
                    setFetching(false);
                });
        }

        fetchBtn.addEventListener('click', fetchArkFleetCount);
        fetchArkFleetCount();

        syncBtn.addEventListener('click', function() {
            if (!apiCount || apiCount <= 0) {
                Swal.fire({
                    title: 'Cannot synchronize',
                    text: 'Fetch a valid ARK-Fleet count first.',
                    icon: 'warning',
                });
                return;
            }

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
