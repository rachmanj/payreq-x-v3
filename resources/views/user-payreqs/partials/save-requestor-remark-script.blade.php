<script>
    $(document).on('click', '.save-requestor-remark', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        var text = $btn.closest('.requestor-reply-wrap').find('.requestor-remark-input').val();
        $btn.prop('disabled', true);
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'PUT',
                requestor_remarks: text
            },
            success: function (res) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(res.message);
                } else {
                    alert(res.message);
                }
                window.location.reload();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message :
                    'Could not save your reply.';
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                } else {
                    alert(msg);
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
</script>
