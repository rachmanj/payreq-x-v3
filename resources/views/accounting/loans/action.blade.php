@hasanyrole('superadmin|admin|cashier')
<form action="{{ route('accounting.loans.destroy', $model->id) }}" method="POST">
  @csrf @method('DELETE')
    <a class="btn btn-xs btn-warning" href="{{ route('accounting.loans.edit', $model->id) }}" class="btn btn-xs btn-primary">edit</a>
    <a class="btn btn-xs btn-success" href="{{ route('accounting.loans.show', $model->id) }}" class="btn btn-xs btn-primary">installment</a>
  <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are You sure You want to delete this record?')">delete</button>
</form>
@endhasanyrole