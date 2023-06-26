<form action="{{ route('mypayreqs.destroy', $model->id) }}" method="POST">
    @csrf @method('DELETE')
    @if ($model->editable)
        @if ($model->type == 'advance')
            <a href="{{ route('payreq-advance.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
        @else 
            <a href="{{ route('payreq-other.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
        @endif
    @endif
    @if ($model->deletable)
        <button class="btn btn-xs btn-danger" onclick="return confirm('Are You sure You want to delete this record?')">delete</button>  
    @endif
    @if($model->printable)
        <a href="{{ route('mypayreqs.print', $model->id) }}" class="btn btn-xs btn-info" target="_blank">print</a>
    @endif
</form>
