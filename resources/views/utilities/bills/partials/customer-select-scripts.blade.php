@php
    use App\Models\UtilityCustomer;
@endphp
<script>
    $(function() {
        const tipeLabels = @json(UtilityCustomer::TIPE);
        const emptyLabel = '—';

        function updateCustomerInfoPanel() {
            const $opt = $('#utility_customer_id').find(':selected');
            if (!$opt.length || $opt.val() === '') {
                $('#uci-id-pelanggan, #uci-nama, #uci-lokasi, #uci-jenis, #uci-nomor-meter, #uci-tipe, #uci-project').text(emptyLabel);
                return;
            }
            const tipeKey = $opt.data('tipe') || 'postpaid';
            $('#uci-id-pelanggan').text($opt.data('id-pelanggan') || emptyLabel);
            $('#uci-nama').text($opt.data('nama') || emptyLabel);
            $('#uci-lokasi').text($opt.data('lokasi') || emptyLabel);
            $('#uci-jenis').text($opt.data('jenis') || emptyLabel);
            const nomorMeter = $opt.data('nomor-meter');
            $('#uci-nomor-meter').text(nomorMeter ? nomorMeter : emptyLabel);
            $('#uci-tipe').text(tipeLabels[tipeKey] || tipeKey || emptyLabel);
            $('#uci-project').text($opt.data('project') || emptyLabel);
        }

        function customerSelectMatcher(tipe) {
            return function(params, data) {
                const $opt = $(data.element);
                if ($opt.val() === '') {
                    return data;
                }
                const optTipe = $opt.data('tipe') || 'postpaid';
                if (optTipe !== tipe) {
                    return null;
                }
                const term = $.trim(params.term).toLowerCase();
                if (term === '') {
                    return data;
                }
                const nama = String($opt.data('nama') || '').toLowerCase();
                const lokasi = String($opt.data('lokasi') || '').toLowerCase();
                const idPelanggan = String($opt.data('id-pelanggan') || '').toLowerCase();
                const nomorMeter = String($opt.data('nomor-meter') || '').toLowerCase();
                const project = String($opt.data('project') || '').toLowerCase();
                const haystack = (data.text + ' ' + nama + ' ' + lokasi + ' ' + idPelanggan + ' ' + nomorMeter + ' ' + project).toLowerCase();
                return haystack.indexOf(term) > -1 ? data : null;
            };
        }

        function initCustomerSelect(tipe) {
            const $select = $('#utility_customer_id');

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.select2({
                theme: 'bootstrap4',
                placeholder: 'Cari nama, lokasi, ID pelanggan, atau no. meter...',
                matcher: customerSelectMatcher(tipe)
            });

            $select.off('select2:open.utilityCustomer').on('select2:open.utilityCustomer', function() {
                $('.select2-container--open .select2-search__field').attr('placeholder',
                    'Cari nama, lokasi, ID pelanggan, atau no. meter...');
            });

            const $selected = $select.find(':selected');
            if ($selected.length && $selected.val() !== '' && ($selected.data('tipe') || 'postpaid') !== tipe) {
                $select.val('').trigger('change');
            }

            updateCustomerInfoPanel();
        }

        $('#utility_customer_id').on('change', updateCustomerInfoPanel);

        window.__utilityBillInitCustomerSelect = initCustomerSelect;
    });
</script>
