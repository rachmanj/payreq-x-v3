@extends('templates.main')

@section('title_page')
    Announcements
@endsection

@section('breadcrumb_title')
    announcements
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Announcements</h3>
                    <div class="card-tools">
                        <a href="{{ route('announcements.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Announcement
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="announcementsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center align-middle">No</th>
                                    <th class="align-middle">Content Preview</th>
                                    <th class="align-middle">Start Date</th>
                                    <th class="align-middle">Duration</th>
                                    <th class="align-middle">End Date</th>
                                    <th class="align-middle">Target Roles</th>
                                    <th class="align-middle">Status</th>
                                    <th class="text-center align-middle">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($announcements as $index => $announcement)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                                {!! Str::limit(strip_tags($announcement->content), 50) !!}
                                            </div>
                                        </td>
                                        <td>{{ $announcement->start_date->format('d/m/Y') }}</td>
                                        <td>{{ $announcement->duration_days }} days</td>
                                        <td>{{ $announcement->end_date->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($announcement->target_roles)
                                                @foreach ($announcement->target_roles as $role)
                                                    <span class="badge badge-info mr-1">{{ ucfirst($role) }}</span>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>
                                            @if ($announcement->status === 'active')
                                                @if ($announcement->is_current)
                                                    <span class="badge badge-success">🟢 Active & Current</span>
                                                @elseif($announcement->is_expired)
                                                    <span class="badge badge-warning">🟡 Active but Expired</span>
                                                @else
                                                    <span class="badge badge-primary">🔵 Active (Future)</span>
                                                @endif
                                            @else
                                                <span class="badge badge-secondary">⚫ Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('announcements.show', $announcement) }}"
                                                    class="btn btn-sm btn-info mr-2" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('announcements.edit', $announcement) }}"
                                                    class="btn btn-sm btn-warning mr-2" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('announcements.toggle_status', $announcement) }}"
                                                    method="POST" style="display: inline;"
                                                    onsubmit="return confirm('Are you sure you want to change the status of this announcement?')">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-success mr-2"
                                                        title="Toggle Status">
                                                        <i
                                                            class="fas fa-toggle-{{ $announcement->status === 'active' ? 'on' : 'off' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('announcements.destroy', $announcement) }}"
                                                    method="POST" style="display: inline;"
                                                    onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('#announcementsTable').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "order": [
                    [2, "desc"]
                ], // Order by start date descending
                "columnDefs": [{
                        "orderable": false,
                        "targets": [8]
                    } // Disable ordering on Actions column
                ]
            });
        });
    </script>
@endsection
