<script>
    (function() {
        if (typeof Swal === 'undefined') {
            return;
        }

        const vjSwalBaseClass = {
            container: 'vj-swal-container',
            popup: 'vj-swal-popup',
            title: 'vj-swal-title',
            htmlContainer: 'vj-swal-html',
            actions: 'vj-swal-actions',
            cancelButton: 'vj-action-item vj-action-print',
        };

        const confirmVariantClass = {
            danger: 'vj-btn vj-btn-danger',
            success: 'vj-btn vj-btn-success',
            warning: 'vj-btn vj-btn-warning',
            primary: 'vj-btn vj-btn-primary',
        };

        function mergeCustomClass(options) {
            const variant = options.confirmVariant || 'primary';
            const userClass = options.customClass || {};

            return Object.assign({}, vjSwalBaseClass, userClass, {
                confirmButton: userClass.confirmButton || confirmVariantClass[variant] ||
                    confirmVariantClass.primary,
            });
        }

        function buildOptions(options) {
            const config = Object.assign({
                buttonsStyling: false,
                cancelButtonText: 'Cancel',
            }, options);

            config.customClass = mergeCustomClass(config);
            delete config.confirmVariant;

            return config;
        }

        window.VjSwal = {
            fire: function(options) {
                return Swal.fire(buildOptions(options || {}));
            },
        };
    })();
</script>
