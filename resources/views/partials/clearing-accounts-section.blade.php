<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Section D — Clearing Account (SAP)</h3>
                <small class="text-muted ml-2">Akun clearing dipantau via parameter dashboard_clearing_accounts</small>
            </div>
            <div class="card-body p-0">
                @if ($clearing_cards === [])
                    <p class="text-muted text-center py-4 mb-0">
                        Belum ada akun clearing yang dipantau (isi parameter dashboard_clearing_accounts).
                    </p>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th width="22%">Akun</th>
                                    <th width="15%">Saldo SAP</th>
                                    <th width="12%">Debit Hari Ini</th>
                                    <th width="12%">Kredit Hari Ini</th>
                                    <th width="12%">Net Hari Ini</th>
                                    <th width="10%">Tx Hari Ini</th>
                                    <th width="10%">Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($clearing_cards as $account)
                                    <tr>
                                        <td>
                                            <strong>{{ $account['code'] }}</strong><br>
                                            <small class="text-muted">{{ $account['name'] }}</small>
                                            @if ($account['error'])
                                                <br><span class="badge badge-danger mt-1">{{ $account['error'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($account['error'])
                                                <span class="text-muted">-</span>
                                            @else
                                                <small>{{ number_format($account['balance'], 2) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($account['error'])
                                                <span class="text-muted">-</span>
                                            @else
                                                <small>{{ number_format($account['today_debit'], 2) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($account['error'])
                                                <span class="text-muted">-</span>
                                            @else
                                                <small>{{ number_format($account['today_credit'], 2) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($account['error'])
                                                <span class="text-muted">-</span>
                                            @else
                                                <small>{{ number_format($account['today_net'], 2) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($account['error'])
                                                <span class="text-muted">-</span>
                                            @else
                                                <span class="badge badge-info">{{ $account['today_count'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-xs btn-outline-secondary btn-clearing-detail"
                                                data-code="{{ $account['code'] }}"
                                                data-name="{{ $account['name'] }}"
                                                @disabled((bool) $account['error'])>
                                                <i class="fas fa-search"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="clearing-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearing-modal-title">Transaksi Clearing</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Tanggal Mulai</label>
                        <input type="date" class="form-control form-control-sm" id="clearing-start-date">
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold">Tanggal Selesai</label>
                        <input type="date" class="form-control form-control-sm" id="clearing-end-date">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-primary" id="clearing-load-btn">
                            <i class="fas fa-sync-alt"></i> Muat
                        </button>
                    </div>
                </div>
                <div id="clearing-alert" class="alert alert-danger d-none"></div>
                <div class="row mb-2">
                    <div class="col-md-4">
                        <div class="bg-light p-2 rounded small">
                            <span class="font-weight-bold">Opening:</span>
                            <span id="clearing-opening">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light p-2 rounded small">
                            <span class="font-weight-bold">Closing:</span>
                            <span id="clearing-closing">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light p-2 rounded small text-right">
                            <span class="font-weight-bold">Periode:</span>
                            <span id="clearing-period">-</span>
                        </div>
                    </div>
                </div>
                <table id="clearing-table" class="table table-bordered table-striped table-sm w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Doc No</th>
                            <th>Project</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Kredit</th>
                            <th class="text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // Section D — Clearing Account SAP transactions
        $(function() {
            const clearingUrl = @json(route($clearing_transactions_route));
            let clearingTable = null;
            let activeClearingCode = null;

            const toYmd = (d) => {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            };

            const today = new Date();
            const thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            $('#clearing-start-date').val(toYmd(thirtyDaysAgo));
            $('#clearing-end-date').val(toYmd(today));

            function formatCurrency(value) {
                const number = Number(value ?? 0);
                return Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(number);
            }

            function formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return dateString;
                const day = String(date.getDate()).padStart(2, '0');
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return `${day}-${months[date.getMonth()]}-${date.getFullYear()}`;
            }

            function resetClearingSummary() {
                $('#clearing-opening').text('-');
                $('#clearing-closing').text('-');
                $('#clearing-period').text('-');
            }

            function initClearingTable() {
                if (clearingTable) {
                    clearingTable.destroy();
                    $('#clearing-table tbody').empty();
                }

                clearingTable = $('#clearing-table').DataTable({
                    processing: true,
                    serverSide: false,
                    searching: false,
                    lengthChange: false,
                    pageLength: 25,
                    data: [],
                    columns: [{
                            data: null,
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            },
                            orderable: false
                        },
                        {
                            data: 'posting_date',
                            render: function(data) {
                                return formatDate(data);
                            }
                        },
                        {
                            data: 'description',
                            defaultContent: '-'
                        },
                        {
                            data: 'doc_num',
                            defaultContent: '-'
                        },
                        {
                            data: 'project_code',
                            defaultContent: '-'
                        },
                        {
                            data: 'debit_amount',
                            className: 'text-right',
                            render: function(data) {
                                return formatCurrency(data);
                            }
                        },
                        {
                            data: 'credit_amount',
                            className: 'text-right',
                            render: function(data) {
                                return formatCurrency(data);
                            }
                        },
                        {
                            data: 'running_balance',
                            className: 'text-right',
                            render: function(data) {
                                return formatCurrency(data);
                            }
                        },
                    ],
                    order: [[1, 'asc']],
                });
            }

            function loadClearingTransactions() {
                if (!activeClearingCode) return;

                const startDate = $('#clearing-start-date').val();
                const endDate = $('#clearing-end-date').val();
                const startObj = new Date(startDate);
                const endObj = new Date(endDate);
                const sixMonthsLater = new Date(startObj);
                sixMonthsLater.setMonth(sixMonthsLater.getMonth() + 6);

                $('#clearing-alert').addClass('d-none').text('');
                resetClearingSummary();

                if (endObj > sixMonthsLater) {
                    $('#clearing-alert').removeClass('d-none').text('Date range cannot exceed 6 months.');
                    if (clearingTable) clearingTable.clear().draw();
                    return;
                }

                $('#clearing-load-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Muat...');

                $.get(clearingUrl, {
                    account_code: activeClearingCode,
                    start_date: startDate,
                    end_date: endDate,
                    draw: 1,
                }).done(function(response) {
                    $('#clearing-opening').text(formatCurrency(response.opening_balance ?? 0));
                    $('#clearing-closing').text(formatCurrency(response.closing_balance ?? 0));
                    if (response.start_date && response.end_date) {
                        $('#clearing-period').text(formatDate(response.start_date) + ' s/d ' + formatDate(response.end_date));
                    }
                    if (!clearingTable) initClearingTable();
                    clearingTable.clear();
                    if (response.data && response.data.length > 0) {
                        clearingTable.rows.add(response.data);
                    }
                    clearingTable.draw();
                }).fail(function(xhr) {
                    const message = xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)
                        ? (xhr.responseJSON.error || xhr.responseJSON.message)
                        : (xhr.responseJSON && xhr.responseJSON.errors
                            ? Object.values(xhr.responseJSON.errors).flat().join(' ')
                            : 'Gagal memuat transaksi SAP.');
                    $('#clearing-alert').removeClass('d-none').text(message);
                    if (clearingTable) clearingTable.clear().draw();
                }).always(function() {
                    $('#clearing-load-btn').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Muat');
                });
            }

            $('.btn-clearing-detail').on('click', function() {
                activeClearingCode = $(this).data('code');
                const accountName = $(this).data('name');
                $('#clearing-modal-title').text('Transaksi Clearing — ' + activeClearingCode + ' ' + accountName);
                initClearingTable();
                $('#clearing-modal').modal('show');
                loadClearingTransactions();
            });

            $('#clearing-load-btn').on('click', loadClearingTransactions);

            $('#clearing-modal').on('hidden.bs.modal', function() {
                if (clearingTable) {
                    clearingTable.destroy();
                    clearingTable = null;
                }
                activeClearingCode = null;
                $('#clearing-alert').addClass('d-none').text('');
                resetClearingSummary();
            });
        });
    </script>
@endpush
