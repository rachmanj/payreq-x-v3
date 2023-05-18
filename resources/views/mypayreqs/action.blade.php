<form action="{{ route('mypayreqs.destroy', $model->id) }}" method="POST">
    @csrf @method('DELETE')
    @if ($model->status == 'draft')
        @if ($model->type == 'advance')
            <a href="{{ route('payreq-advance.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
        @else 
            <a href="{{ route('payreq-other.edit', $model->id) }}" class="btn btn-xs btn-warning">edit</a>
        @endif
        <button class="btn btn-xs btn-danger" onclick="return confirm('Are You sure You want to delete this record?')">delete</button>  
    @endif
</form>
