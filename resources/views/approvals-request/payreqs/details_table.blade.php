<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
                <div class="card-tools">
                    @can('edit-submitted-realization')
                        <button type="button" class="btn btn-sm btn-info" id="btn-edit-details">
                            <i class="fas fa-edit"></i> Edit Details
                        </button>
                        <div id="edit-mode-buttons" style="display: none;">
                            <button type="button" class="btn btn-sm btn-success" id="btn-add-row">
                                <i class="fas fa-plus"></i> Add Row
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" id="btn-save-details">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="btn-cancel-edit">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <table class="table table-striped" id="details-table">
                    <thead>
                        <tr>
                            <th>#</td>
                            <th>Desc</td>
                            <th>Department</td>
                            <th>Project</td>
                            <th class="text-right">Amount (IDR)</th>
                            <th class="text-center actions-column" style="display: none;">Actions</th>
                        </tr>
                    </thead>
                    @if ($realization_details->count() > 0)
                        <tbody id="details-tbody">
                            @foreach ($realization_details as $item)
                                <tr data-detail-id="{{ $item->id }}" data-description="{{ $item->description }}"
                                    data-amount="{{ $item->amount }}" data-department-id="{{ $item->department_id }}"
                                    data-project="{{ $item->project }}"
                                    data-unit-no="{{ $item->unit_no }}" data-type="{{ $item->type }}"
                                    data-qty="{{ $item->qty }}" data-uom="{{ $item->uom }}"
                                    data-km-position="{{ $item->km_position }}">
                                    <td class="row-number">{{ $loop->iteration }}</td>
                                    <td class="description-cell">
                                        <div class="description-display">
                                            {{ $item->description }}
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
                                        </div>
                                    </td>
                                    <td class="department-cell">
                                        <div class="department-display">
                                            {{ $item->department ? $item->department->department_name : '-' }}
                                        </div>
                                    </td>
                                    <td class="project-cell">
                                        <div class="project-display">{{ $item->project ?: '-' }}</div>
                                    </td>
                                    <td class="text-right amount-cell">
                                        <div class="amount-display">{{ number_format($item->amount, 2) }}</div>
                                    </td>
                                    <td class="text-center actions-column" style="display: none;">
                                        <button type="button" class="btn btn-xs btn-danger btn-delete-row">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right">Total</td>
                                <td class="text-right"><b
                                        id="total-amount-display">{{ number_format($realization_details->sum('amount'), 2) }}</b>
                                </td>
                                <td class="actions-column" style="display: none;"></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right">Variance</td>
                                <td class="text-right"><b
                                        id="variance-display">{{ number_format($payreq->amount - $realization_details->sum('amount'), 2) }}</b>
                                </td>
                                <td class="actions-column" style="display: none;"></td>
                            </tr>
                            <tr id="amount-warning-row" style="display: none;">
                                <td colspan="5" class="text-center">
                                    <div class="alert alert-warning mb-0" role="alert">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span id="amount-warning-message">Total amount differs from original</span>
                                    </div>
                                </td>
                                <td class="actions-column" style="display: none;"></td>
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
