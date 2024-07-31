{{-- auth()->user->id == created_by --}}
@if(auth()->user()->id == $model->created_by)
<a href="{{ route('user-payreqs.anggarans.edit', $model->id) }}" class="btn btn-xs btn-warning {{ $model->editable === 0 ? 'disabled' : '' }}">edit</a>
@endif
@if($model->filename)
<a href="{{ asset('file_upload/') . '/'. $model->filename }}" class="btn btn-xs btn-success" target=_blank>show</a>
@endif
