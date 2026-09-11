@can('manage_activities')
    @if ($showActivityColumn ?? false)
        <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
        <script>
            const approvalActivityConfig = {
                canManage: true,
                locked: @json($activityLocked ?? false),
                openActivities: @json($openActivities),
                headerActivityId: @json($realization->activity_id),
                storeUrl: @json(route('approvals.activities.store')),
                openUrl: @json(route('approvals.activities.open')),
                csrfToken: @json(csrf_token()),
            };

            let approvalOpenActivities = approvalActivityConfig.openActivities || [];
            let activitySelectTarget = null;

            function initApprovalActivitySelects() {
                if (!approvalActivityConfig.canManage) {
                    return;
                }

                $('.activity-select2').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                });

                $('#approval-create-activity-modal .select2-modal').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $('#approval-create-activity-modal'),
                });
            }

            function buildActivitySelectOptions(selectedId, excluded) {
                let options = '<option value="">— Ikut header realisasi —</option>';
                approvalOpenActivities.forEach(function(activity) {
                    const selected = String(activity.id) === String(selectedId || '') ? 'selected' : '';
                    options += `<option value="${activity.id}" ${selected}>${activity.code} — ${activity.name}</option>`;
                });
                return options;
            }

            function renderActivityEditCell(row) {
                const activityId = row.data('activity-id') || '';
                const excluded = row.data('activity-excluded') === 1 || row.data('activity-excluded') === '1';
                const disabled = approvalActivityConfig.locked ? 'disabled' : '';

                return `
                    <div class="activity-edit-cell">
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input activity-excluded-input" id="activity-excluded-${row.index()}"
                                ${excluded ? 'checked' : ''} ${disabled}>
                            <label class="custom-control-label" for="activity-excluded-${row.index()}">Tanpa kegiatan (override)</label>
                        </div>
                        <select class="form-control form-control-sm activity-id-input activity-select2" ${disabled}>
                            ${buildActivitySelectOptions(activityId, excluded)}
                        </select>
                    </div>
                `;
            }

            function bindActivityExcludedToggle(row) {
                const excludedInput = row.find('.activity-excluded-input');
                const activitySelect = row.find('.activity-id-input');

                excludedInput.off('change.approvalActivity').on('change.approvalActivity', function() {
                    if ($(this).is(':checked')) {
                        activitySelect.val('').prop('disabled', true);
                    } else {
                        activitySelect.prop('disabled', approvalActivityConfig.locked);
                    }
                    activitySelect.trigger('change.select2');
                });

                if (excludedInput.is(':checked')) {
                    activitySelect.val('').prop('disabled', true);
                }
            }

            function appendActivityToNewRow(newRow) {
                if (!approvalActivityConfig.canManage || approvalActivityConfig.locked) {
                    return;
                }

                const activityCell = $('<td class="activity-cell"></td>');
                activityCell.html(renderActivityEditCell(newRow));
                newRow.find('.project-cell').after(activityCell);
                bindActivityExcludedToggle(newRow);
                activityCell.find('.activity-select2').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                });
            }

            function transformActivityRowToEdit(row) {
                if (!approvalActivityConfig.canManage) {
                    return;
                }

                const activityCell = row.find('.activity-cell');
                if (!activityCell.length) {
                    return;
                }

                if (approvalActivityConfig.locked) {
                    return;
                }

                activityCell.html(renderActivityEditCell(row));
                bindActivityExcludedToggle(row);
                activityCell.find('.activity-select2').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                });
            }

            function collectActivityDetailFields(row, detail) {
                if (!approvalActivityConfig.canManage || approvalActivityConfig.locked) {
                    return detail;
                }

                detail.activity_excluded = row.find('.activity-excluded-input').is(':checked') ? 1 : 0;
                detail.activity_id = row.find('.activity-id-input').val() || null;

                return detail;
            }

            function collectHeaderActivityId() {
                if (!approvalActivityConfig.canManage || approvalActivityConfig.locked) {
                    return null;
                }

                return $('#header_activity_id').val() || null;
            }

            function addActivityToPayload(payload) {
                if (!approvalActivityConfig.canManage || approvalActivityConfig.locked) {
                    return payload;
                }

                payload.activity_id = collectHeaderActivityId();

                return payload;
            }

            function registerApprovalActivityOption(activity) {
                const exists = approvalOpenActivities.some(function(item) {
                    return String(item.id) === String(activity.id);
                });

                if (!exists) {
                    approvalOpenActivities.push(activity);
                }

                const label = `${activity.code} — ${activity.name}`;
                const optionHtml = `<option value="${activity.id}">${label}</option>`;

                $('#header_activity_id').append(optionHtml);

                $('.activity-id-input').each(function() {
                    if (!$(this).find(`option[value="${activity.id}"]`).length) {
                        $(this).append(optionHtml);
                    }
                });
            }

            function selectCreatedActivity(activity) {
                registerApprovalActivityOption(activity);

                if (activitySelectTarget === 'header') {
                    $('#header_activity_id').val(activity.id).trigger('change');
                } else if (activitySelectTarget && activitySelectTarget.length) {
                    activitySelectTarget.find('.activity-excluded-input').prop('checked', false);
                    const select = activitySelectTarget.find('.activity-id-input');
                    select.prop('disabled', false).val(activity.id).trigger('change');
                }

                activitySelectTarget = null;
            }

            $(function() {
                initApprovalActivitySelects();

                $('#btn-create-activity').on('click', function() {
                    activitySelectTarget = 'header';
                    const defaultProject = $(this).data('default-project') || '';
                    $('#modal_activity_project').val(defaultProject);
                    $('#approval-create-activity-modal').modal('show');
                });

                $(document).on('focusin', '.activity-id-input', function() {
                    activitySelectTarget = $(this).closest('tr');
                });

                $('#modal_activity_mode').on('change', function() {
                    const isReklas = $(this).val() === 'reklasifikasi';
                    $('#modal_activity_account_id').prop('required', isReklas);
                });

                $('#approval-create-activity-form').on('submit', function(e) {
                    e.preventDefault();

                    const submitBtn = $('#btn-submit-create-activity');
                    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

                    const formData = {
                        _token: approvalActivityConfig.csrfToken,
                        name: $('#modal_activity_name').val(),
                        periode: $('#modal_activity_periode').val(),
                        project: $('#modal_activity_project').val() || null,
                        mode: $('#modal_activity_mode').val(),
                        account_id: $('#modal_activity_account_id').val() || null,
                        anggaran_ids: $('#modal_activity_anggaran_ids').val() || [],
                    };

                    $.ajax({
                        url: approvalActivityConfig.storeUrl,
                        method: 'POST',
                        data: formData,
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Kegiatan berhasil dibuat.',
                                timer: 2000,
                                showConfirmButton: false,
                            });

                            selectCreatedActivity(response.activity);
                            $('#approval-create-activity-modal').modal('hide');
                            $('#approval-create-activity-form')[0].reset();
                            $('#modal_activity_periode').val('{{ now()->format('Y-m') }}');
                            $('#modal_activity_project').val($('#btn-create-activity').data('default-project') || '');
                        },
                        error: function(xhr) {
                            let message = xhr.responseJSON?.message || 'Gagal membuat kegiatan.';
                            if (xhr.responseJSON?.errors) {
                                message = Object.values(xhr.responseJSON.errors).flat().join('\n');
                            }

                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: message,
                            });
                        },
                        complete: function() {
                            submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
                        },
                    });
                });
            });
        </script>
    @endif
@endcan
