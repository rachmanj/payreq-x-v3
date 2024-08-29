{{-- auth()->user->id == created_by --}}
@if(auth()->user()->id == $model->created_by)
    @if($model->editable === 1)
        <a href="{{ route('user-payreqs.anggarans.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
    @endif
@endif
@if($model->filename)
    <a href="{{ asset('file_upload/') . '/'. $model->filename }}" class="btn btn-xs btn-success" target=_blank>show</a>
@endif
