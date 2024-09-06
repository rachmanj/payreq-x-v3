
<div class="col-lg-3 col-6">
    <!-- small box -->
  <div class="small-box {{ $avg_completion_days > 7 ? 'bg-danger' : 'bg-success' }}">
    <div class="inner text-center">
      <h1>{{ number_format($avg_completion_days, 2) }}</h1>

      {{-- <p>Average Completion Days</p> --}}
    </div>
    <div class="icon">
      <i class="ion ion-stats-bars"></i>
    </div>
    <a href="#" class="small-box-footer" style="cursor: default">Average Completion Days</a>
  </div>
</div>

@can('see_vj_not_posted')
<div class="col-lg-3 col-6">
    <!-- small box -->
  <div class="small-box bg-primary }}">
    <div class="inner text-center">
      <h1>{{ $vj_not_posted }}</h1>

      {{-- <p>Average Completion Days</p> --}}
    </div>
    <div class="icon">
      <i class="ion ion-stats-bars"></i>
    </div>
    <a href="{{ route('accounting.sap-sync.index', ['project' => '000H']) }}" class="small-box-footer" style="cursor: default">VJ to Synced</a>
  </div>
</div>
@endcan