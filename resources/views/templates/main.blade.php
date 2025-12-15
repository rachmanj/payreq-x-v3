<!DOCTYPE html>

<html lang="en">
    @include('templates.partials.head')

    <head>
        <!-- Toastr CSS -->
        <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
        <style>
            /* Global SweetAlert2 styles to ensure proper positioning */
            .swal2-container {
                z-index: 9999 !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 10px !important;
                background-color: rgba(0, 0, 0, 0.4) !important;
                box-sizing: border-box !important;
            }

            .swal2-popup {
                position: relative !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                max-width: 100% !important;
                margin: 0 auto !important;
            }

            body.swal2-shown {
                padding-right: 0 !important;
                overflow-y: hidden !important;
                /* Prevent background scrolling */
            }

            body.swal2-height-auto {
                height: 100% !important;
            }

            /* Make sure footer doesn't overlap with modal */
            body.swal2-shown .main-footer {
                z-index: 1 !important;
            }
        </style>
        @stack('styles')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>

    <body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
        <div class="wrapper">

            @include('templates.partials.topbar')
            @include('templates.partials.sidebar')

            <!-- Content Wrapper. Contains page content -->
            <div class="content-wrapper">
                <!-- Content Header (Page header) -->
                <div class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1 class="m-0">@yield('title_page')</h1>
                            </div><!-- /.col -->
                            @include('templates.partials.breadcrumb')
                        </div><!-- /.row -->
                    </div><!-- /.container-fluid -->
                </div>
                <!-- /.content-header -->

                <!-- Main content -->
                <div class="content">
                    <div class="container-fluid">

                        @yield('content')

                    </div><!-- /.container-fluid -->
                </div>
                <!-- /.content -->
            </div>
            <!-- /.content-wrapper -->

            @include('templates.partials.footer')

        </div>
        <!-- ./wrapper -->

        <!-- REQUIRED SCRIPTS -->
        @include('templates.partials.script')

        <!-- Toastr JS -->
        <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

        <!-- Toastr Notifications -->
        <script>
            // Configure Toastr options
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            @if (Session::has('success'))
                toastr.success("{{ Session::get('success') }}");
            @endif

            @if (Session::has('error'))
                toastr.error("{{ Session::get('error') }}");
            @endif

            @if (Session::has('info'))
                toastr.info("{{ Session::get('info') }}");
            @endif

            @if (Session::has('warning'))
                toastr.warning("{{ Session::get('warning') }}");
            @endif
        </script>

        <!-- SweetAlert2 for permission errors -->
        @if (Session::has('alert_type'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    @if (Session::get('alert_type') == 'error')
                        Swal.fire({
                            icon: 'error',
                            title: "{{ Session::get('alert_title', 'Error') }}",
                            text: "{{ Session::get('alert_message', 'An error occurred') }}",
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#d33'
                        });
                    @endif
                });
            </script>
        @endif

        <!-- Modals -->
        @yield('modals')

        <!-- Sidebar State Management -->
        <script>
            $(document).ready(function() {
                // Restore sidebar state from localStorage
                if (localStorage.getItem('sidebar-collapsed') === 'true') {
                    $('body').addClass('sidebar-collapse');
                }

                // Save sidebar state when toggled
                $('[data-widget="pushmenu"]').on('click', function() {
                    setTimeout(function() {
                        if ($('body').hasClass('sidebar-collapse')) {
                            localStorage.setItem('sidebar-collapsed', 'true');
                        } else {
                            localStorage.setItem('sidebar-collapsed', 'false');
                        }
                    }, 100);
                });

                // Auto-expand parent menu items if child is active
                $('.nav-item.has-treeview').each(function() {
                    if ($(this).find('.nav-link.active').length > 0) {
                        $(this).addClass('menu-open');
                    }
                });
            });
        </script>

        <!-- Additional Scripts -->
        @stack('scripts')

    </body>

</html>
