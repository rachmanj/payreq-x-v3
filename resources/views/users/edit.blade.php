@extends('templates.main')

@section('title_page')
    Users
@endsection

@section('breadcrumb_title')
    users
@endsection

@section('content')
    <form action="{{ route('users.roles_user_update', $user->id) }}" method="POST">
        @csrf @method('PUT')
        <div class="row">

            <div class="col-7">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Edit Data</div>
                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-primary float-right"><i
                                class="fas fa-arrow-left"></i> Back</a>
                    </div>

                    <div class="card-body">

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" name="name" value="{{ $user->name }}" class="form-control"
                                        required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" name="username" value="{{ old('username', $user->username) }}"
                                        class="form-control @error('username') is-invalid @enderror">
                                    @error('username')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="text" name="email" value="{{ old('email', $user->email) }}"
                                        class="form-control @error('email') is-invalid @enderror">
                                    @error('email')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for='project'>Project</label>
                            <select name="project" class="form-control">
                                <option value="">-- Select Project --</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->code }}"
                                        {{ $project->code === $user->project ? 'selected' : '' }}>{{ $project->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for='department_id'>Department</label>
                            <select name="department_id" class="form-control">
                                <option value="">-- Select Department --</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}"
                                        {{ $department->id === $user->department_id ? 'selected' : '' }}>
                                        {{ $department->department_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror">
                                    @error('password')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="password_confirmation">Password Confirmation</label>
                                    <input type="password" name="password_confirmation"
                                        class="form-control @error('password_confirmation') is-invalid @enderror">
                                    @error('password_confirmation')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Save</button>
                        </div>

                    </div>
                </div>
            </div> {{-- col-7 --}}

            <div class="col-5">
                <div class="card card-secondary">
                    <div class="card-header">
                        <div class="card-title">Assign Roles</div>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            @if ($roles)
                                <div class="form-group">
                                    @foreach ($roles as $role)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="role-{{ $role->id }}"
                                                name="role[]" value="{{ $role->id }}"
                                                {{ in_array($role->name, $userRoles) ? 'checked="checked"' : '' }}>
                                            <label class="form-check-label"
                                                for="role-{{ $role->id }}">{{ $role->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div> {{-- row --}}
    </form>
@endsection

@section('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
@endsection
