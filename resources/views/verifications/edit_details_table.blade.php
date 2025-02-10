<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                {{-- <h3 class="card-title">Details</h3> --}}
                {{-- button back --}}
                <a href="{{ route('verifications.index') }}" class="btn btn-sm btn-success float-right"><i
                        class="fas fa-arrow-left"></i> Back</a>
                <button type="submit" form="save_verification" action="{{ route('verifications.index') }}"
                    class="btn btn-sm btn-primary"><i class="fas fa-save"></i> SAVE</button>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</td>
                            <th>Desc</td>
                            <th>Current Account</td>
                            <th>New Account</th>
                            <td></td>

                            {{-- @hasanyrole('superadmin|admin|cashier|cashier_017|cashier_bo') --}}
                            @can('edit_verification_project')
                                <th>Project</th>
                                <th>Dept</th>
                            @endcan
                            {{-- @endhasanyrole --}}

                            <th class="text-right">Amount (IDR)</th>
                        </tr>
                    </thead>
                    @if ($realization_details->count() > 0)
                        <tbody>
                            <form action="{{ route('verifications.save') }}" id="save_verification" method="POST">
                                @csrf
                                <input type="hidden" name="realization_id" value="{{ $realization->id }}">
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
                                        <td>{{ $item->account_id ? $item->account->account_number : '-' }}</td>
                                        <td colspan="2">
                                            <div class="form-group">
                                                <div class="d-flex mb-2">
                                                    <input type="hidden" value="{{ $item->id }}"
                                                        name="realization_details[{{ $key }}][id]">
                                                    <input type="text" id="account_number_{{ $item->id }}"
                                                        name="realization_details[{{ $key }}][account_number]"
                                                        class="form-control" style="width: 200px;"
                                                        placeholder="Account Number">
                                                    <button type="button" class="btn btn-sm btn-primary ml-2"
                                                        onclick="openAccountModal({{ $item->id }})">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                                <input type="text" id="account_name_{{ $item->id }}"
                                                    class="form-control" style="border: none; background: transparent;"
                                                    placeholder="Account Name" disabled>
                                            </div>
                                        </td>

                                        {{-- @hasanyrole('superadmin|admin|cashier|cashier_017|cashier_bo') --}}
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
                                        {{-- @endhasanyrole --}}

                                        <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </form>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-right"></td>
                                <td style="width: 10%;"></td>
                                <td class="text-right" style="width: 10%;">TOTAL</td>
                                <td class="text-right">
                                    <b>{{ number_format($realization_details->sum('amount'), 2) }}</b>
                                </td>
                            </tr>
                        </tfoot>
                    @else
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center">No Data Found</td>
                            </tr>
                        </tbody>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

@section('modals')
    <!-- Account Selection Modal -->
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
    </style>
@endpush

@push('scripts')
    <script type="text/javascript">
        // Define functions in global scope
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

                    // Destroy existing DataTable if it exists
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

                    // Remove any duplicates from response
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

                    // First populate the tbody
                    uniqueAccounts.forEach(function(account) {
                        const accountNumber = account.account_number.replace(/'/g, "\\'");
                        const accountName = account.account_name.replace(/'/g, "\\'");

                        tbody.append(`
                            <tr>
                                <td>${accountNumber}</td>
                                <td>${accountName}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" 
                                        onclick="selectAccount('${accountNumber}', '${accountName}')">
                                        Select
                                    </button>
                                </td>
                            </tr>
                        `);
                    });

                    // Then initialize DataTable
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

        function selectAccount(accountNumber, accountName) {
            $(`#account_number_${window.currentDetailId}`).val(accountNumber);
            $(`#account_name_${window.currentDetailId}`).val(accountName);
            $('#accountModal').modal('hide');
        }

        // Document ready function
        $(document).ready(function() {
            // Remove the DataTable initialization from here
            // We'll initialize it after loading the data
        });
    </script>
@endpush
