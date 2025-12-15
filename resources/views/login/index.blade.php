<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AccountingOne</title>

    <!-- Google Font: Source Sans Pro -->
    {{-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback"> --}}
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <style>
        body.login-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            width: 100%;
            max-width: 450px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo a {
            font-size: 2.5rem;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            font-weight: 700;
        }

        .login-logo a small {
            font-size: 1.2rem;
            display: block;
            margin-top: 0.5rem;
            font-weight: 400;
            opacity: 0.9;
        }

        .login-card-body {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .login-box-msg {
            font-size: 1.1rem;
            font-weight: 500;
            color: #495057;
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .feature-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .whats-new-card {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: none;
            overflow: hidden;
        }

        .whats-new-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
        }

        .whats-new-card .card-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .whats-new-card .card-body {
            background: #f8f9fa;
        }

        .feature-item {
            padding: 1rem;
            margin-bottom: 0.75rem;
            background: white;
            border-radius: 8px;
            border-left: 4px solid;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .feature-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .feature-item.border-primary {
            border-left-color: #667eea;
        }

        .feature-item.border-success {
            border-left-color: #28a745;
        }

        .feature-item.border-info {
            border-left-color: #17a2b8;
        }

        .feature-item.border-warning {
            border-left-color: #ffc107;
        }

        .feature-icon {
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }

        .input-group-text {
            background: #f8f9fa;
            border-right: none;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="#"><b>Accounting</b>One<small>v.4.0</small></a>
        </div>
        <!-- /.login-logo -->
        <div class="card">
            <div class="card-body login-card-body">
                <div class="text-center mb-3">
                    <span class="feature-badge">
                        <i class="fas fa-rocket mr-1"></i> Major Update Available
                    </span>
                </div>

                @if (session()->has('success'))
                    <div class="alert alert-success alert-dismissible">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    </div>
                @endif

                @if (session()->has('loginError'))
                    <div class="alert alert-danger alert-dismissible">
                        {{ session('loginError') }}
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    </div>
                @endif

                <p class="login-box-msg">Sign in to start your session</p>

                <form action="#" method="post">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" name="username"
                            class="form-control @error('username') is-invalid @enderror" placeholder="Username"
                            autofocus>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                        @error('username')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                            </button>
                        </div>
                    </div>
                </form>

                <p class="mb-0 mt-3 text-center">
                    <a href="{{ route('register') }}" class="text-center">
                        <i class="fas fa-user-plus mr-1"></i>Register new account
                    </a>
                </p>
            </div>
            <!-- /.login-card-body -->
        </div>

        <!-- What's New Card -->
        <div class="card whats-new-card card-outline mt-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-star mr-2"></i>What's New in v.4.0
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool text-white" data-toggle="collapse"
                        data-target="#whatsNewCollapse" aria-expanded="false" aria-controls="whatsNewCollapse">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div id="whatsNewCollapse" class="collapse" aria-labelledby="whatsNewHeading">
                <div class="card-body">
                    <div class="feature-item border-primary">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-bars feature-icon text-primary"></i>
                            <div>
                                <strong class="d-block mb-1">Modern Sidebar Navigation</strong>
                                <small class="text-muted">Completely redesigned navigation with a sleek sidebar menu.
                                    Better organization, easier access to all features, and improved user experience.
                                    The sidebar stays visible while you work, making navigation faster and more
                                    intuitive.</small>
                            </div>
                        </div>
                    </div>
                    <div class="feature-item border-success">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-palette feature-icon text-success"></i>
                            <div>
                                <strong class="d-block mb-1">Enhanced UI/UX Design</strong>
                                <small class="text-muted">Dark, fixed navigation bar with modern styling. Improved
                                    visual hierarchy and better use of screen space. All menu items are now organized
                                    in an expandable tree structure for quick access.</small>
                            </div>
                        </div>
                    </div>
                    <div class="feature-item border-info">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-mobile-alt feature-icon text-info"></i>
                            <div>
                                <strong class="d-block mb-1">Better Mobile Experience</strong>
                                <small class="text-muted">Responsive sidebar that adapts perfectly to mobile devices.
                                    Collapsible menu with smooth animations. Your sidebar preferences are remembered
                                    across sessions.</small>
                            </div>
                        </div>
                    </div>
                    <div class="feature-item border-secondary">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-database feature-icon text-secondary"></i>
                            <div>
                                <strong class="d-block mb-1">Projects/Departments Admin Stability</strong>
                                <small class="text-muted">Fixed DataTables load errors by safely parsing legacy sync
                                    timestamps and added toastr feedback for SAP sync and visibility toggles. Admins now
                                    get clear, non-blocking notifications while keeping lists stable.</small>
                            </div>
                        </div>
                    </div>
                    <div class="feature-item border-warning">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-bolt feature-icon text-warning"></i>
                            <div>
                                <strong class="d-block mb-1">Improved Performance</strong>
                                <small class="text-muted">Faster navigation with optimized menu structure. Active route
                                    highlighting and auto-expanding menus help you know exactly where you are in the
                                    system.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.login-box -->

    <!-- jQuery -->
    <script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('adminlte/dist/js/adminlte.min.js') }}"></script>
    <script>
        // Toggle chevron icon on collapse
        $('#whatsNewCollapse').on('show.bs.collapse', function() {
            $(this).closest('.card').find('.fa-chevron-down').removeClass('fa-chevron-down').addClass(
                'fa-chevron-up');
        });
        $('#whatsNewCollapse').on('hide.bs.collapse', function() {
            $(this).closest('.card').find('.fa-chevron-up').removeClass('fa-chevron-up').addClass(
                'fa-chevron-down');
        });
    </script>
</body>

</html>
