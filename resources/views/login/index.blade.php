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
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="#"><b>Accounting</b>One<small> | v.3.9</small></a>
        </div>
        <!-- /.login-logo -->
        <div class="card">
            <div class="card-body login-card-body">

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
                        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                    </div>
                </form>

                <p class="mb-0">
                    <a href="{{ route('register') }}" class="text-center">Register new account</a>
                </p>
            </div>
            <!-- /.login-card-body -->
        </div>

        <!-- What's New Card -->
        <div class="card card-outline card-info mt-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-star text-warning"></i> What's New in v.3.9
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-toggle="collapse" data-target="#whatsNewCollapse"
                        aria-expanded="false" aria-controls="whatsNewCollapse">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div id="whatsNewCollapse" class="collapse" aria-labelledby="whatsNewHeading">
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">
                            <strong><i class="fas fa-paper-plane text-primary"></i> Direct SAP B1 Submission</strong>
                            <br>
                            <small class="text-muted">Submit verification journals directly to SAP B1 with automatic
                                journal
                                number tracking. No more manual Excel export needed!</small>
                        </li>
                        <li class="mb-2">
                            <strong><i class="fas fa-link text-success"></i> Enhanced Reference Fields</strong>
                            <br>
                            <small class="text-muted">Realization numbers and verification journal numbers are now
                                automatically included in SAP B1 journal entries for better traceability.</small>
                        </li>
                        <li>
                            <strong><i class="fas fa-shield-alt text-info"></i> Improved Audit Trail</strong>
                            <br>
                            <small class="text-muted">Complete submission history with detailed logging for all SAP B1
                                integration activities.</small>
                        </li>
                    </ul>
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
