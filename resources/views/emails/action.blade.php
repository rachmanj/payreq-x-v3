@can('send_notif')
  @if ($model->email)
    <a href="{{ route('emails.push', $model->id) }}" class="btn btn-xs btn-info" data-toggle="tooltip" data-placement="top" title="Send email notif to user">send notif</a>
  @endif
@endcan
