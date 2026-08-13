@php
    $canEditProject = auth()->user()->can('edit_verification_project');
    $columnCount = $canEditProject ? 6 : 4;
    $totalColspan = $columnCount - 1;
@endphp

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="card-title mb-0">
                    <i class="fas fa-list"></i> Verification Details
                </h3>
                <div class="vj-inline-actions">
                    <a href="{{ route('verifications.index') }}" class="vj-action-item vj-action-print">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" form="save_verification" class="vj-btn vj-btn-primary">
                        <i class="fas fa-save"></i> SAVE
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <form action="{{ route('verifications.save') }}" id="save_verification" method="POST">
                    @csrf
                    <input type="hidden" name="realization_id" value="{{ $realization->id }}">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Desc</th>
                                <th>Account</th>
                                @can('edit_verification_project')
                                    <th>Project</th>
                                    <th>Dept</th>
                                @endcan
                                <th class="text-right">Amount (IDR)</th>
                            </tr>
                        </thead>
                        @if ($realization_details->count() > 0)
                            <tbody>
                                @foreach ($realization_details as $key => $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->description }}
                                            @if ($item->unit_no != null)
                                                <br />
                                                @if ($item->type === 'fuel')
                                                    <small>Unit No: {{ $item->unit_no }}, {{ $item->type }}
                                                        {{ $item->qty }} {{ $item->uom }}. HM:
                                                        {{ $item->km_position }}</small>
                                                @else
                                                    <small>{{ $item->type }}, HM: {{ $item->km_position }}</small>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <div class="form-group mb-0">
                                                <input type="hidden" value="{{ $item->id }}"
                                                    name="realization_details[{{ $key }}][id]">
                                                <div class="d-flex mb-2 align-items-center">
                                                    <div class="position-relative flex-shrink-0" style="width: 200px;">
                                                        <input type="text" id="account_number_{{ $item->id }}"
                                                            name="realization_details[{{ $key }}][account_number]"
                                                            class="form-control" style="width: 200px;"
                                                            value="{{ $item->account_id ? $item->account->account_number : '' }}"
                                                            placeholder="Account Number" autocomplete="off">
                                                        <div id="account_suggestions_{{ $item->id }}"
                                                            class="account-suggestions-dropdown list-group shadow-sm border bg-white">
                                                        </div>
                                                    </div>
                                                    <button type="button"
                                                        class="vj-action-item vj-action-item-btn vj-action-item-xs vj-action-edit ml-2"
                                                        title="Search account"
                                                        onclick="openAccountModal({{ $item->id }})">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                                <input type="text" id="account_name_{{ $item->id }}"
                                                    class="form-control" style="border: none; background: transparent;"
                                                    value="{{ $item->account_id ? $item->account->account_name : '' }}"
                                                    placeholder="Account Name" disabled>
                                            </div>
                                        </td>
                                        @can('edit_verification_project')
                                            <td>
                                                <select name="realization_details[{{ $key }}][project]"
                                                    class="form-control">
                                                    @foreach ($projects as $project)
                                                        <option value="{{ $project->code }}"
                                                            {{ $project->code == $item->project ? 'selected' : '' }}>
                                                            {{ $project->code }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="realization_details[{{ $key }}][department_id]"
                                                    class="form-control">
                                                    @foreach ($departments as $department)
                                                        <option value="{{ $department->id }}"
                                                            {{ $department->id == $item->department_id ? 'selected' : '' }}>
                                                            {{ $department->akronim }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endcan
                                        <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="{{ $totalColspan }}" class="text-right"><strong>TOTAL</strong></td>
                                    <td class="text-right">
                                        <strong>{{ number_format($realization_details->sum('amount'), 2) }}</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        @else
                            <tbody>
                                <tr>
                                    <td colspan="{{ $columnCount }}" class="text-center">No Data Found</td>
                                </tr>
                            </tbody>
                        @endif
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

@section('modals')
    <div class="modal fade" id="accountModal" tabindex="-1" role="dialog" aria-labelledby="accountModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountModalLabel">Select Account</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-striped" id="accountsTable">
                        <thead>
                            <tr>
                                <th>Account Number</th>
                                <th>Account Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="vj-action-item vj-action-print" data-dismiss="modal">
                        <i class="fas fa-times"></i>
                        <span>Close</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .dataTables_filter {
            width: 100%;
            margin-bottom: 15px;
        }

        .dataTables_filter label {
            width: 100%;
            display: flex !important;
            flex-direction: column;
            gap: 5px;
        }

        .dataTables_filter label span {
            font-weight: bold;
            color: #495057;
        }

        .dataTables_filter input {
            width: 100% !important;
            margin-left: 0 !important;
            height: 38px;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }

        .account-suggestions-dropdown {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            z-index: 1050;
            max-height: 220px;
            overflow-y: auto;
            margin-top: 2px;
            border-radius: 0.25rem;
        }

        .account-suggestions-dropdown .list-group-item {
            font-size: 0.875rem;
            cursor: pointer;
            border-left: none;
            border-right: none;
        }

        .account-suggestions-dropdown .list-group-item:first-child {
            border-top: none;
        }

        .account-suggestions-dropdown .list-group-item:last-child {
            border-bottom: none;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/javascript">
        function openAccountModal(detailId) {
            window.currentDetailId = detailId;
            $('#accountModal').modal('show');
            loadAccounts();
        }

        function loadAccounts() {
            $.ajax({
                url: '{{ route('accounts.list') }}',
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    console.log('Loading accounts...');
                    let tbody = $('#accountsTable tbody');
                    tbody.html(`
                        <tr>
                            <td colspan="3" class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Loading accounts...
                            </td>
                        </tr>
                    `);
                },
                success: function(response) {
                    console.log('Raw response:', response);
                    let tbody = $('#accountsTable tbody');
                    tbody.empty();

                    if ($.fn.DataTable.isDataTable('#accountsTable')) {
                        $('#accountsTable').DataTable().destroy();
                        console.log('Existing DataTable destroyed');
                    }

                    if (!Array.isArray(response)) {
                        console.error('Invalid response:', response);
                        tbody.append(`
                            <tr>
                                <td colspan="3" class="text-center text-danger">
                                    Invalid response format
                                </td>
                            </tr>
                        `);
                        return;
                    }

                    const uniqueAccounts = [...new Map(response.map(item => [item.account_number, item]))
                        .values()
                    ];

                    console.log('Unique accounts count:', uniqueAccounts.length);

                    if (uniqueAccounts.length === 0) {
                        console.log('No accounts found in response');
                        tbody.append(`
                            <tr>
                                <td colspan="3" class="text-center">
                                    No accounts found
                                </td>
                            </tr>
                        `);
                        return;
                    }

                    uniqueAccounts.forEach(function(account) {
                        const accountNumber = account.account_number.replace(/'/g, "\\'");
                        const accountName = account.account_name.replace(/'/g, "\\'");

                        tbody.append(`
                            <tr>
                                <td>${accountNumber}</td>
                                <td>${accountName}</td>
                                <td>
                                    <button type="button" class="vj-btn vj-btn-success"
                                        onclick="selectAccount('${accountNumber}', '${accountName}')">
                                        Select
                                    </button>
                                </td>
                            </tr>
                        `);
                    });

                    try {
                        const dataTable = $('#accountsTable').DataTable({
                            pageLength: 10,
                            order: [
                                [0, 'asc']
                            ],
                            language: {
                                search: "<span>Search Account</span>",
                                lengthMenu: "Show _MENU_ entries",
                                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                                paginate: {
                                    first: "First",
                                    last: "Last",
                                    next: "Next",
                                    previous: "Previous"
                                }
                            },
                            dom: "<'row'<'col-sm-12'f>>" +
                                "<'row'<'col-sm-12'tr>>" +
                                "<'row'<'col-sm-5'i><'col-sm-7'p>>",
                        });
                        console.log('DataTable initialized with', uniqueAccounts.length, 'rows');
                    } catch (e) {
                        console.error('Error initializing DataTable:', e);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', {
                        xhr,
                        status,
                        error
                    });
                    let tbody = $('#accountsTable tbody');
                    tbody.empty();

                    let errorMessage = 'Error loading accounts. Please try again later.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }

                    tbody.append(`
                        <tr>
                            <td colspan="3" class="text-center text-danger">
                                ${errorMessage}
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function setAccountFields(detailId, accountNumber, accountName) {
            $(`#account_number_${detailId}`).val(accountNumber);
            $(`#account_name_${detailId}`).val(accountName);
            $(`#account_suggestions_${detailId}`).hide().empty();
        }

        function selectAccount(accountNumber, accountName) {
            setAccountFields(window.currentDetailId, accountNumber, accountName);
            $('#accountModal').modal('hide');
            $(`#account_number_${window.currentDetailId}`).trigger('change');
        }

        let accountAutocompleteTimer = null;

        $(document).on('input', 'input[id^="account_number_"]', function() {
            const $input = $(this);
            const detailId = $input.attr('id').replace('account_number_', '');
            const $dropdown = $(`#account_suggestions_${detailId}`);
            const q = $input.val().trim();
            clearTimeout(accountAutocompleteTimer);
            if (q.length < 1) {
                $dropdown.hide().empty();
                return;
            }
            accountAutocompleteTimer = setTimeout(function() {
                $.ajax({
                    url: '{{ route('accounts.autocomplete') }}',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        q: q
                    },
                    success: function(rows) {
                        $dropdown.empty();
                        if (!Array.isArray(rows) || rows.length === 0) {
                            $dropdown.hide();
                            return;
                        }
                        rows.forEach(function(row) {
                            const label = row.account_number + ' — ' + row.account_name;
                            const $btn = $('<button type="button" class="list-group-item list-group-item-action">')
                                .text(label)
                                .on('mousedown', function(e) {
                                    e.preventDefault();
                                    setAccountFields(detailId, row.account_number, row
                                        .account_name);
                                    $input.trigger('change');
                                });
                            $dropdown.append($btn);
                        });
                        $dropdown.show();
                    },
                    error: function() {
                        $dropdown.hide().empty();
                    }
                });
            }, 250);
        });

        $(document).on('blur', 'input[id^="account_number_"]', function() {
            const detailId = $(this).attr('id').replace('account_number_', '');
            setTimeout(function() {
                $(`#account_suggestions_${detailId}`).hide().empty();
            }, 200);
        });

    </script>
@endpush
