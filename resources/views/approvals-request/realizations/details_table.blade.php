<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="card-title mb-0">
                    <i class="fas fa-list"></i> Details
                </h3>
                <div class="vj-inline-actions">
                    @can('edit-submitted-realization')
                        <button type="button" class="vj-action-item vj-action-item-xs vj-action-edit" id="btn-edit-details">
                            <i class="fas fa-edit"></i>
                            <span>Edit Details</span>
                        </button>
                        <div id="edit-mode-buttons" class="vj-inline-actions" style="display: none;">
                            <button type="button" class="vj-action-item vj-action-item-xs vj-decision-approve" id="btn-add-row">
                                <i class="fas fa-plus"></i>
                                <span>Add Row</span>
                            </button>
                            <button type="button" class="vj-btn vj-btn-primary btn-sm" id="btn-save-details">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="button" class="vj-action-item vj-action-item-xs vj-action-print" id="btn-cancel-edit">
                                <i class="fas fa-times"></i>
                                <span>Cancel</span>
                            </button>
                        </div>
                    @endcan
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover mb-0" id="details-table">
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
                    @if ($document_details->count() > 0)
                        <tbody id="details-tbody">
                            @foreach ($document_details as $item)
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
                                            @include('partials.realization-detail-meta', ['detail' => $item])
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
                                        <button type="button"
                                            class="vj-action-item vj-action-item-btn vj-action-item-xs vj-action-cancel btn-delete-row">
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
                                        id="total-amount-display">{{ number_format($document_details->sum('amount'), 2) }}</b>
                                </td>
                                <td class="actions-column" style="display: none;"></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right">Variance</td>
                                <td class="text-right"><b
                                        id="variance-display">{{ number_format($payreq->amount - $document_details->sum('amount'), 2) }}</b>
                                </td>
                                <td class="actions-column" style="display: none;"></td>
                            </tr>
                            <tr id="amount-warning-row" style="display: none;">
                                <td colspan="5" class="text-center">
                                    <div class="vj-alert vj-alert-warning mb-0" role="alert">
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
