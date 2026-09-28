@if (!empty($warning))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>{{ $warning['title'] }}</strong> {{ $warning['message'] }}
        <a href="{{ route('cashier.bapsb.create', ['period' => $warning['period']]) }}" class="alert-link ml-2">Create BAPSB</a>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif
