@extends('templates.main')

@section('title_page')
    Edit Bank Transaction
@endsection

@section('breadcrumb_title')
    bank-transactions/edit
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Edit Bank Transaction</h3>
                </div>
                <div class="card-body">

                    <form action="{{ route('cashier.bank-transactions.update', $journal->id) }}" method="POST"
                        id="transaction-form">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date">Posting Date</label>
                                    <input type="date" class="form-control @error('date') is-invalid @enderror"
                                        id="date" name="date" value="{{ old('date', $journal->date) }}" required>
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="project">Project</label>
                                    <input type="text" class="form-control @error('project') is-invalid @enderror"
                                        id="project" name="project" value="{{ old('project', $journal->project) }}"
                                        readonly>
                                    @error('project')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_account">Bank Account</label>
                                    <select class="form-control select2 @error('bank_account') is-invalid @enderror"
                                        id="bank_account_select" style="width: 100%;">
                                        <option value="">-- Select Bank Account --</option>
                                    </select>
                                    <input type="hidden" name="bank_account" id="bank_account"
                                        value="{{ old('bank_account', $journal->bank_account ?? '') }}">
                                    @error('bank_account')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <input type="text" class="form-control @error('description') is-invalid @enderror"
                                        id="description" name="description"
                                        value="{{ old('description', $journal->description) }}" required>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <h4 class="mt-4">Transaction Details</h4>
                        <div class="mb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="add-detail-btn">
                                <i class="fas fa-plus"></i> Add Detail
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm" id="details-table">
                                <thead>
                                    <tr>
                                        <th width="10%">Realization Date</th>
                                        <th width="15%">Account Code</th>
                                        <th width="10%">Debit/Credit</th>
                                        <th width="25%">Description</th>
                                        <th width="15%">Project</th>
                                        <th width="10%">Cost Center</th>
                                        <th width="10%">Amount</th>
                                        <th width="5%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Details will be added dynamically via JavaScript -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="6" class="text-right">Total:</th>
                                        <th id="total-amount">0.00</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Hidden inputs to store details data -->
                        <div id="detail-inputs">
                            <!-- Will be filled dynamically -->
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-sm" id="save-transaction-btn">Update
                                Transaction</button>
                            <a href="{{ route('cashier.bank-transactions.index') }}"
                                class="btn btn-secondary btn-sm">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for adding/editing detail -->
    <div class="modal fade" id="detail-modal" tabindex="-1" role="dialog" aria-labelledby="detail-modal-label"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detail-modal-label">Transaction Detail</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="detail-form">
                        <input type="hidden" id="modal-edit-id" value="">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="modal-account-code">Account Code</label>
                                    <select class="form-control select2" id="modal-account-code"
                                        name="modal-account-code" required style="width: 100%;">
                                        <option value="">-- Select Account --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="modal-description">Description</label>
                                    <input type="text" class="form-control" id="modal-description"
                                        name="modal-description" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="modal-amount">Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="modal-amount"
                                        name="modal-amount" required>
                                </div>
                            </div>
                        </div>
                        <!-- Hidden fields -->
                        <input type="hidden" id="modal-realization-date" value="{{ date('Y-m-d') }}">
                        <input type="hidden" id="modal-project" value="{{ Auth::user()->project }}">
                        <input type="hidden" id="modal-cost-center"
                            value="{{ Auth::user()->department->sap_code ?? '' }}">
                        <input type="hidden" id="modal-debit-credit" value="debit">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="save-detail-btn">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
    <style>
        .select2-container--default .select2-selection--single {
            height: 38px;
            line-height: 38px;
        }
    </style>
@endpush

@push('scripts')
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize toastr
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap4'
            });

            // Add change event to bank account select
            $('#bank_account_select').on('change', function() {
                const selectedValue = $(this).val();
                $('#bank_account').val(selectedValue);
                console.log('Bank account selection changed:', selectedValue);
                console.log('Hidden bank_account field updated:', $('#bank_account').val());
            });

            // Load account codes and bank accounts
            loadAccountCodes();
            loadBankAccounts();

            // Counter for detail rows
            let detailCounter = 0;

            // Load existing details
            loadExistingDetails();

            // Add detail button click
            $('#add-detail-btn').on('click', function() {
                // Reset form
                $('#detail-form')[0].reset();
                $('#modal-edit-id').val('');

                // Set defaults for hidden fields
                $('#modal-realization-date').val($('#date').val()); // Use main transaction date
                $('#modal-project').val('{{ Auth::user()->project }}');
                $('#modal-cost-center').val('{{ Auth::user()->department->sap_code ?? '' }}');
                $('#modal-debit-credit').val('debit');

                // Clear select2
                $('#modal-account-code').val('').trigger('change');

                // Change modal title
                $('#detail-modal-label').text('Add Transaction Detail');

                // Show modal
                $('#detail-modal').modal('show');
            });

            // Save detail button click
            $('#save-detail-btn').on('click', function() {
                // Validate form
                if (!validateDetailForm()) {
                    return;
                }

                // Get form values
                const editId = $('#modal-edit-id').val();
                const realizationDate = $('#date').val(); // Use the main transaction date
                const accountCode = $('#modal-account-code').val();
                const accountName = $('#modal-account-code option:selected').text();
                const debitCredit = 'debit'; // Always debit for this transaction type
                const description = $('#modal-description').val();
                const project = $('#modal-project').val();
                const costCenter = $('#modal-cost-center').val();
                const amount = parseFloat($('#modal-amount').val()).toFixed(2);

                if (editId) {
                    // Update existing row
                    updateDetailRow(editId, realizationDate, accountCode, accountName, debitCredit,
                        description, project, costCenter, amount);
                    toastr.success('Detail updated successfully');
                } else {
                    // Add new row
                    addDetailRow(realizationDate, accountCode, accountName, debitCredit, description,
                        project, costCenter, amount);
                    toastr.success('Detail added successfully');
                }

                // Hide modal
                $('#detail-modal').modal('hide');
            });

            // Submit form
            $('#transaction-form').on('submit', function(e) {
                // Check if there are details
                if ($('#details-table tbody tr').length === 0) {
                    e.preventDefault();
                    toastr.error('Please add at least one transaction detail');
                    return false;
                }

                // Check if bank account is selected
                const bankAccount = $('#bank_account_select').val();
                console.log('Selected bank account:', bankAccount);

                if (!bankAccount) {
                    e.preventDefault();
                    toastr.error('Please select a bank account');
                    $('#bank_account_select').focus();
                    return false;
                }

                // Ensure project is a string
                const projectValue = $('#project').val();
                console.log('Project value type:', typeof projectValue);
                console.log('Project value:', projectValue);

                // Force project to be a simple string
                $('#project').val(String(projectValue));
                console.log('Updated project value:', $('#project').val());
                console.log('Updated project value type:', typeof $('#project').val());

                // Update the hidden field with the selected value
                $('#bank_account').val(bankAccount);
                console.log('Hidden bank_account field updated:', $('#bank_account').val());

                // Confirm submission
                e.preventDefault();
                Swal.fire({
                    title: 'Update Transaction',
                    text: 'Are you sure you want to update this transaction?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update it',
                    cancelButtonText: 'No, cancel',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#dc3545',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('Form is being submitted with bank account:', $('#bank_account')
                            .val());
                        console.log('Form is being submitted with project:', $('#project').val());
                        // Submit the form
                        e.target.submit();
                    }
                });
            });

            // Function to load existing details
            function loadExistingDetails() {
                @foreach ($journal->verificationJournalDetails as $index => $detail)
                    addDetailRow(
                        '{{ $detail->realization_date }}',
                        '{{ $detail->account_code }}',
                        '{{ $detail->account_code }}', // This will be replaced by account name from API
                        '{{ $detail->debit_credit }}',
                        '{{ $detail->description }}',
                        '{{ $detail->project }}',
                        '{{ $detail->cost_center }}',
                        '{{ $detail->amount }}'
                    );
                @endforeach
            }

            // Function to add a detail row
            function addDetailRow(realizationDate, accountCode, accountName, debitCredit, description, project,
                costCenter, amount) {
                detailCounter++;

                // Format the realization date for display
                const displayDate = new Date(realizationDate).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });

                // Create the row
                const row = `
                    <tr id="detail-row-${detailCounter}">
                        <td>${displayDate}</td>
                        <td>${accountCode} - ${accountName.split(' - ')[1] || ''}</td>
                        <td>${debitCredit.charAt(0).toUpperCase() + debitCredit.slice(1)}</td>
                        <td>${description}</td>
                        <td>${project}</td>
                        <td>${costCenter}</td>
                        <td class="text-right">${parseFloat(amount).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-warning btn-xs edit-detail mr-1" data-id="${detailCounter}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-xs delete-detail" data-id="${detailCounter}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;

                // Add the row to the table
                $('#details-table tbody').append(row);

                // Add hidden inputs
                $('#detail-inputs').append(`
                    <input type="hidden" name="realization_date[]" value="${realizationDate}" id="realization_date_${detailCounter}">
                    <input type="hidden" name="account_code[]" value="${accountCode}" id="account_code_${detailCounter}">
                    <input type="hidden" name="debit_credit[]" value="${debitCredit}" id="debit_credit_${detailCounter}">
                    <input type="hidden" name="detail_description[]" value="${description}" id="detail_description_${detailCounter}">
                    <input type="hidden" name="project[]" value="${String(project)}" id="project_${detailCounter}">
                    <input type="hidden" name="cost_center[]" value="${costCenter}" id="cost_center_${detailCounter}">
                    <input type="hidden" name="amount[]" value="${amount}" id="amount_${detailCounter}">
                `);

                // Update total
                updateTotal();

                // Bind buttons
                bindButtons();
            }

            // Function to update a detail row
            function updateDetailRow(id, realizationDate, accountCode, accountName, debitCredit, description,
                project, costCenter, amount) {
                // Format the realization date for display
                const displayDate = new Date(realizationDate).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });

                // Update the row in the table
                const row = $(`#detail-row-${id}`);
                row.find('td:eq(0)').text(displayDate);
                row.find('td:eq(1)').text(`${accountCode} - ${accountName.split(' - ')[1] || ''}`);
                row.find('td:eq(2)').text(debitCredit.charAt(0).toUpperCase() + debitCredit.slice(1));
                row.find('td:eq(3)').text(description);
                row.find('td:eq(4)').text(project);
                row.find('td:eq(5)').text(costCenter);
                row.find('td:eq(6)').text(parseFloat(amount).toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                }));

                // Update hidden inputs
                $(`#realization_date_${id}`).val(realizationDate);
                $(`#account_code_${id}`).val(accountCode);
                $(`#debit_credit_${id}`).val(debitCredit);
                $(`#detail_description_${id}`).val(description);
                $(`#project_${id}`).val(project);
                $(`#cost_center_${id}`).val(costCenter);
                $(`#amount_${id}`).val(amount);

                // Update total
                updateTotal();
            }

            // Function to validate the detail form
            function validateDetailForm() {
                const fields = [{
                        id: 'modal-account-code',
                        name: 'Account Code'
                    },
                    {
                        id: 'modal-description',
                        name: 'Description'
                    },
                    {
                        id: 'modal-amount',
                        name: 'Amount'
                    }
                ];

                for (const field of fields) {
                    if (!$('#' + field.id).val()) {
                        toastr.error(`${field.name} is required`);
                        $('#' + field.id).focus();
                        return false;
                    }
                }

                return true;
            }

            // Function to update the total amount
            function updateTotal() {
                let total = 0;
                $('input[name="amount[]"]').each(function() {
                    total += parseFloat($(this).val() || 0);
                });
                $('#total-amount').text(total.toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                }));
            }

            // Function to bind action buttons
            function bindButtons() {
                // Edit button
                $('.edit-detail').off('click').on('click', function() {
                    const id = $(this).data('id');

                    // Get detail data
                    const realizationDate = $(`#realization_date_${id}`).val();
                    const accountCode = $(`#account_code_${id}`).val();
                    const debitCredit = $(`#debit_credit_${id}`).val();
                    const description = $(`#detail_description_${id}`).val();
                    const project = $(`#project_${id}`).val();
                    const costCenter = $(`#cost_center_${id}`).val();
                    const amount = $(`#amount_${id}`).val();

                    // Fill form
                    $('#modal-edit-id').val(id);
                    $('#modal-description').val(description);
                    $('#modal-amount').val(amount);

                    // Set hidden fields
                    $('#modal-realization-date').val(realizationDate);
                    $('#modal-project').val(project);
                    $('#modal-cost-center').val(costCenter);
                    $('#modal-debit-credit').val(debitCredit);

                    // Select account code
                    if ($(`#modal-account-code option[value="${accountCode}"]`).length) {
                        $('#modal-account-code').val(accountCode).trigger('change');
                    } else {
                        // If option doesn't exist yet, wait for the accounts to load and then set it
                        // This assumes accounts are loaded asynchronously
                        const checkExist = setInterval(function() {
                            if ($(`#modal-account-code option[value="${accountCode}"]`).length) {
                                $('#modal-account-code').val(accountCode).trigger('change');
                                clearInterval(checkExist);
                            }
                        }, 100);
                    }

                    // Change modal title
                    $('#detail-modal-label').text('Edit Transaction Detail');

                    // Show modal
                    $('#detail-modal').modal('show');
                });

                // Delete button
                $('.delete-detail').off('click').on('click', function() {
                    const id = $(this).data('id');

                    // Confirm deletion
                    Swal.fire({
                        title: 'Confirm Deletion',
                        text: 'Are you sure you want to remove this detail?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, remove it!',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Remove the row and hidden inputs
                            $(`#detail-row-${id}`).remove();
                            $(`#realization_date_${id}`).remove();
                            $(`#account_code_${id}`).remove();
                            $(`#debit_credit_${id}`).remove();
                            $(`#detail_description_${id}`).remove();
                            $(`#project_${id}`).remove();
                            $(`#cost_center_${id}`).remove();
                            $(`#amount_${id}`).remove();

                            // Update total
                            updateTotal();

                            // Show success message
                            toastr.success('Detail removed successfully');
                        }
                    });
                });
            }

            // Function to load account codes
            function loadAccountCodes() {
                console.log('Loading account codes...');
                $.ajax({
                    url: "{{ route('accounts.list') }}",
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Accounts loaded:', response);

                        const accountSelect = $('#modal-account-code');
                        accountSelect.empty();
                        accountSelect.append('<option value="">-- Select Account --</option>');

                        const userProject = "{{ Auth::user()->project }}";
                        console.log('User project:', userProject);

                        if (response && response.length > 0) {
                            // Add all accounts - filtering will be handled on the server side
                            response.forEach(function(account) {
                                accountSelect.append(
                                    `<option value="${account.account_number}">${account.account_number} - ${account.account_name}</option>`
                                );
                            });
                            console.log('Total accounts added to select:', response.length);
                        } else {
                            console.warn('No accounts returned from server');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading account codes:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        toastr.error('Failed to load account codes: ' + error);
                    }
                });
            }

            // Function to load bank accounts
            function loadBankAccounts() {
                console.log('Loading bank accounts...');
                const currentBankAccount = "{{ old('bank_account', $journal->bank_account ?? '') }}";
                console.log('Current bank account:', currentBankAccount);

                $.ajax({
                    url: "{{ route('accounts.bank_list') }}",
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Bank accounts loaded:', response);

                        const bankAccountSelect = $('#bank_account_select');
                        bankAccountSelect.empty();
                        bankAccountSelect.append('<option value="">-- Select Bank Account --</option>');

                        const userProject = "{{ Auth::user()->project }}";
                        console.log('User project:', userProject);

                        if (response && response.length > 0) {
                            // Add each bank account to the select
                            response.forEach(function(account) {
                                const accountNumber = account.account_number ? account
                                    .account_number.toString() : '';
                                const accountName = account.account_name ? account.account_name
                                    .toString() : '';
                                const isSelected = currentBankAccount == accountNumber ?
                                    'selected' : '';

                                bankAccountSelect.append(
                                    `<option value="${accountNumber}" ${isSelected}>${accountNumber} - ${accountName}</option>`
                                );
                            });

                            // Trigger change to update hidden input if we have a selected value
                            if (currentBankAccount) {
                                $('#bank_account').val(currentBankAccount);
                                bankAccountSelect.trigger('change');
                            }

                            console.log('Total bank accounts added to select:', response.length);
                        } else {
                            console.warn('No bank accounts found');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading bank accounts:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        toastr.error('Failed to load bank accounts: ' + error);
                    }
                });
            }
        });
    </script>
@endpush
