{{-- Error checking to prevent getKey() issues --}}
@if (!isset($model) || !is_object($model) || !method_exists($model, 'getKey'))
    <span class="text-danger">Invalid data</span>
@else
    @if (
        $model->project == auth()->user()->project ||
            auth()->user()->roles->intersect(['admin', 'superadmin'])->isNotEmpty())
        <div class="btn-group btn-group-sm" role="group">
            @if ($model->status == 'onhand')
                <button type="button" class="btn btn-sm btn-warning" data-toggle="modal"
                    data-target="#bilyet-release-{{ $model->id }}" title="Edit/Release">
                    <i class="fas fa-edit"></i>
                </button>
            @elseif($model->status == 'release')
                <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
                    data-target="#bilyet-cair-{{ $model->id }}" title="Cairkan">
                    <i class="fas fa-money-bill"></i>
                </button>
            @endif

            @if ($model->status != 'void')
                <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                    data-target="#bilyet-void-{{ $model->id }}" title="Void">
                    <i class="fas fa-ban"></i>
                </button>
            @endif

            <button type="button" class="btn btn-sm btn-info" data-toggle="modal"
                data-target="#bilyet-view-{{ $model->id }}" title="View Details">
                <i class="fas fa-eye"></i>
            </button>

            <a href="{{ route('cashier.bilyets.history', $model->id) }}" class="btn btn-sm btn-outline-secondary"
                title="View History">
                <i class="fas fa-history"></i>
            </a>

            @if (auth()->user()->hasRole('superadmin'))
                <a href="{{ route('cashier.bilyets.edit', $model->id) }}" class="btn btn-sm btn-outline-warning"
                    title="Superadmin Edit (All Fields)">
                    <i class="fas fa-cog"></i>
                </a>
            @endif

            @can('delete_bilyet')
                @if ($model->status == 'onhand')
                    <form action="{{ route('cashier.bilyets.destroy', $model->id) }}" method="POST" style="display:inline;"
                        class="ml-2">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-secondary"
                            onclick="return confirm('Are you sure you want delete this record?')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    @endif

    {{-- Modal Release (for onhand status) --}}
    @if ($model->status == 'onhand')
        <div class="modal fade" id="bilyet-release-{{ $model->id }}">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Release {{ $model->type }} no {{ $model->prefix . $model->nomor }}</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form action="{{ route('cashier.bilyets.update', $model->id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            @php
                                $isReleaseErrorContext = old('bilyet_id') == $model->id;
                            @endphp
                            <input type="hidden" name="bilyet_id" value="{{ $model->id }}">
                            <div class="form-group">
                                <label for="bilyet_date">Bilyet Date</label>
                                <input type="date" name="bilyet_date" class="form-control"
                                    value="{{ $isReleaseErrorContext ? old('bilyet_date') : optional($model->bilyet_date)->format('Y-m-d') }}">
                            </div>
                            <div class="form-group">
                                <label for="cair_date">Cair Date (Optional)</label>
                                <input type="date" name="cair_date" class="form-control"
                                    value="{{ $isReleaseErrorContext ? old('cair_date') : optional($model->cair_date)->format('Y-m-d') }}">
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="text" name="amount"
                                    class="form-control{{ $isReleaseErrorContext && $errors->has('amount') ? ' is-invalid' : '' }}"
                                    value="{{ $isReleaseErrorContext ? old('amount') : ($model->amount ?? '') }}">
                                @if ($isReleaseErrorContext && $errors->has('amount'))
                                    <small class="text-danger">{{ $errors->first('amount') }}</small>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="remarks">Purpose</label>
                                <textarea name="remarks" class="form-control">{{ $isReleaseErrorContext ? old('remarks') : $model->remarks }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Cair (for release status) --}}
    @if ($model->status == 'release')
        <div class="modal fade" id="bilyet-cair-{{ $model->id }}">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Cairkan {{ $model->type }} no {{ $model->prefix . $model->nomor }}
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form action="{{ route('cashier.bilyets.update', $model->id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="bilyet_date">Bilyet Date</label>
                                <input type="date" name="bilyet_date" class="form-control"
                                    value="{{ $model->bilyet_date ? $model->bilyet_date->format('Y-m-d') : '' }}"
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="cair_date">Cair Date <span class="text-danger">*</span></label>
                                <input type="date" name="cair_date" class="form-control"
                                    value="{{ old('cair_date', $model->cair_date ? $model->cair_date->format('Y-m-d') : '') }}"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="text" name="amount" class="form-control"
                                    value="{{ $model->amount }}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="remarks">Purpose</label>
                                <textarea name="remarks" class="form-control" readonly>{{ $model->remarks }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-money-bill"></i>
                                Cairkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Void (for all non-void status) --}}
    @if ($model->status != 'void')
        <div class="modal fade" id="bilyet-void-{{ $model->id }}">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Void {{ $model->type }} no {{ $model->prefix . $model->nomor }}</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form action="{{ route('cashier.bilyets.void', $model->id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Confirmation</strong>
                            </div>
                            <p>Are you sure you want to void this bilyet?</p>
                            <p><strong>Status will be changed to VOID</strong></p>
                            <p class="text-muted small">Note: Other data (remarks, amount, dates) will remain
                                unchanged.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Void</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal View Details (for all status) --}}
    <div class="modal fade" id="bilyet-view-{{ $model->id }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Detail {{ $model->type }} no {{ $model->prefix . $model->nomor }}</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nomor:</strong></td>
                                    <td>{{ $model->full_nomor }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td>{{ $model->type_label }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @if ($model->status == 'onhand')
                                            <span class="badge badge-primary">{{ $model->status_label }}</span>
                                        @elseif($model->status == 'release')
                                            <span class="badge badge-warning">{{ $model->status_label }}</span>
                                        @elseif($model->status == 'cair')
                                            <span class="badge badge-success">{{ $model->status_label }}</span>
                                        @elseif($model->status == 'void')
                                            <span class="badge badge-danger">{{ $model->status_label }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Project:</strong></td>
                                    <td>{{ $model->project }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Receive Date:</strong></td>
                                    <td>{{ $model->receive_date ? date('d-M-Y', strtotime($model->receive_date)) : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Bilyet Date:</strong></td>
                                    <td>{{ $model->bilyet_date ? date('d-M-Y', strtotime($model->bilyet_date)) : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Cair Date:</strong></td>
                                    <td>{{ $model->cair_date ? date('d-M-Y', strtotime($model->cair_date)) : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Amount:</strong></td>
                                    <td>{{ $model->amount ? number_format($model->amount, 0, ',', '.') . ',-' : '-' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    @if ($model->remarks)
                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <h6><strong>Remarks:</strong></h6>
                                <p class="text-muted">{{ $model->remarks }}</p>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif
