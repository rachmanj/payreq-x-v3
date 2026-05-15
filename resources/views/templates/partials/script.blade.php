<!-- jQuery -->
<script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('js/menu-search.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- SweetAlert2 -->
<script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('adminlte/dist/js/adminlte.min.js') }}"></script>

@auth
    @can('akses_approval_request')
        <script>
            window.approverRequestorRepliesInboxUrl = @json(route('approvals.plan.requestor-replies.inbox'));
        </script>
        <script>
            $(function() {
                function refreshApproverRequestorRepliesInbox() {
                    if (typeof window.approverRequestorRepliesInboxUrl === 'undefined') {
                        return;
                    }
                    $.ajax({
                        url: window.approverRequestorRepliesInboxUrl,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(data) {
                            var n = data.unread_count || 0;
                            var $badge = $('#approver-requestor-replies-badge');
                            $badge.text(n);
                            if (n > 0) {
                                $badge.removeClass('d-none');
                            } else {
                                $badge.addClass('d-none');
                            }
                            var $list = $('#approver-requestor-replies-list');
                            if (!data.items || !data.items.length) {
                                $list.html(
                                    '<span class="text-muted">No requestor replies yet.</span>');
                                return;
                            }
                            var html = '';
                            data.items.forEach(function(item) {
                                var fw = item.unread ? 'font-weight-bold' : '';
                                var title = $('<div/>').text(item.title || '').html();
                                var preview = $('<div/>').text(item.preview || '').html();
                                var time = $('<div/>').text(item.time || '').html();
                                html += '<a class="dropdown-item ' + fw + '" href="' + item.url +
                                    '">';
                                html += '<div class="text-truncate">' + title + '</div>';
                                html += '<small class="text-muted">' + preview + '</small>';
                                if (item.time) {
                                    html += '<div><small class="text-muted">' + time +
                                        '</small></div>';
                                }
                                html += '</a>';
                                html += '<div class="dropdown-divider"></div>';
                            });
                            $list.html(html);
                        }
                    });
                }
                refreshApproverRequestorRepliesInbox();
                setInterval(refreshApproverRequestorRepliesInbox, 60000);
                $('#approver-requestor-replies-toggle').parent().on('show.bs.dropdown', function() {
                    refreshApproverRequestorRepliesInbox();
                });
            });
        </script>
    @endcan
@endauth


@yield('scripts')
