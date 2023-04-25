@extends('templates.main')

@section('title_page')
  RAB  
@endsection

@section('breadcrumb_title')
    rab
@endsection

@section('content')
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Edit Data</h3>
            <a href="{{ route('rabs.index') }}" class="btn btn-sm btn-primary float-right"><i class="fas fa-undo"></i> Back</a>
            <input type="submit" name="" id="" value="Save" form="form-edit" class="btn btn-sm btn-success float-right mx-2">
          </div>

            
          <div class="card-body">
            <form action="{{ route('rabs.update', $rab->id) }}" method="POST" id="form-edit" enctype="multipart/form-data">
              @csrf @method('PUT')

              <div class="row">
                <div class="col-6">
                  <div class="form-group">
                    <label for="rab_no">RAB No</label>
                    <input name="rab_no" id="rab_no" value="{{ old('rab_no', $rab->rab_no) }}" class="form-control @error('rab_no') is-invalid @enderror">
                    @error('rab_no')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" value="{{ old('date', $rab->date) }}" class="form-control @error('date') is-invalid @enderror">
                    @error('date')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>
              </div>
    
              <div class="row">
                <div class="col-6">
                  <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" cols="30" rows="2">{{ old('description', $rab->description) }}</textarea>
                    @error('description')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="budget">Budget</label>
                        <input type="text" name="budget" id="budget" value="{{ old('budget', $rab->budget) }}" class="form-control @error('budget') is-invalid @enderror">
                        @error('budget')
                          <div class="invalid-feedback">
                            {{ $message }}
                          </div>
                        @enderror
                      </div>
                </div>
              </div>

              <div class="row">
                <div class="col-6">
                  <div class="form-group">
                    <label for="project_code">Project</label>
                    <select name="project_code" id="project_code" class="form-control select2bs4 @error('project_code') is-invalid @enderror">
                      <option value="">-- select project --</option>
                      @foreach ($projects as $project)
                          <option value="{{ $project }}" {{ $rab->project_code == $project ? 'selected' : '' }}>{{ $project }}</option>
                      @endforeach
                    </select>
                    @error('project_code')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="department_id">Department</label>
                        <select name="department_id" id="department_id" class="form-control select2bs4 @error('department_id') is-invalid @enderror">
                          <option value="">-- select department --</option>
                          @foreach ($departments as $department)
                              <option value="{{ $department->id }}" {{ $rab->department_id === $department->id ? 'selected' : '' }}  >{{ $department->department_name }}</option>
                          @endforeach
                        </select>
                        @error('department_id')
                          <div class="invalid-feedback">
                            {{ $message }}
                          </div>
                        @enderror
                    </div>
                </div>
              </div>
      
              <div class="row">
                <div class="col-6">
                  <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                      <option value="">-- select status --</option>
                      <option value="progress" {{ $rab->status == 'progress' ? 'selected' : '' }}>On Progress</option>
                      <option value="done" {{ $rab->status == 'done' ? 'selected' : '' }}>Done</option>
                      <option value="cancel" {{ $rab->status == 'cancel' ? 'selected' : '' }}>Cancel</option>
                    </select>
                    @error('status')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>

                <div class="col-6">
                  <div class="form-group">
                    <label for="file_upload">Upload RAB</label>
                    <input type="file" name="file_upload" id="file_upload" class="form-control">
                    @error('file_upload')
                      <div class="invalid-feedback">
                        {{ $message }}
                      </div>
                    @enderror
                  </div>
                </div>

              </div>

            </form>
          </div>          
           
        </div>
      </div>
    </div>
@endsection