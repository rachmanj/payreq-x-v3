<form action="{{ route('user-payreqs.destroy', $model->id) }}" method="POST">
    @csrf @method('PUT')
    
    @if ($model->deletable)
        @if ($model->type == 'advance')
            <input type="hidden" name="type" value="advance">
        @else
            <input type="hidden" name="type" value="reimburse">
        @endif
        <button type class="btn btn-xs btn-danger" onclick="return confirm('Are You sure You want to delete this record?')">delete</button>
    @endif
</form>
@if ($model->editable)
    @if ($model->type == 'advance')
        <a href="{{ route('payreq-advance.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
    @else 
        <a href="{{ route('payreq-reimburse.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
    @endif
@endif
@if($model->printable && $model->status !== 'split')
    <a href="{{ route('user-payreqs.print', $model->id) }}" class="btn btn-xs btn-info" target="_blank">print</a>
@endif
