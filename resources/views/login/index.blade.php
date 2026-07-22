<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - AccountingOne</title>

    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    @vite('resources/css/login.css')
</head>

<body class="tw-login-page tw-m-0 tw-flex tw-items-center tw-justify-center tw-px-4 tw-py-10">
    <div class="tw-w-full tw-max-w-md">
        {{-- Brand --}}
        <div class="tw-text-center tw-mb-8">
            <a href="{{ route('login') }}" class="tw-no-underline tw-inline-block">
                <span class="tw-block tw-text-4xl tw-font-bold tw-tracking-tight tw-text-white">
                    Accounting<span class="tw-text-sky-400">One</span>
                </span>
                <span class="tw-block tw-mt-2 tw-text-sm tw-font-medium tw-tracking-widest tw-uppercase tw-text-slate-400">
                    v.4.1
                </span>
            </a>
        </div>

        {{-- Login card --}}
        <div class="tw-rounded-2xl tw-border tw-border-slate-700/80 tw-bg-slate-900/80 tw-px-6 tw-py-8 sm:tw-px-8 tw-shadow-2xl tw-backdrop-blur-sm tw-overflow-hidden">
            <div class="tw-mb-6 tw-text-center">
                <h1 class="tw-mt-0 tw-mb-1 tw-text-xl tw-font-semibold tw-text-white">Welcome back</h1>
                <p class="tw-m-0 tw-text-sm tw-text-slate-400">Sign in to start your session</p>
            </div>

            @if (session()->has('success'))
                <div class="tw-mb-4 tw-rounded-lg tw-border tw-border-emerald-500/40 tw-bg-emerald-500/10 tw-px-4 tw-py-3 tw-text-sm tw-text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('loginError'))
                <div class="tw-mb-4 tw-rounded-lg tw-border tw-border-rose-500/40 tw-bg-rose-500/10 tw-px-4 tw-py-3 tw-text-sm tw-text-rose-300">
                    {{ session('loginError') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="tw-mb-4 tw-rounded-lg tw-border tw-border-rose-500/40 tw-bg-rose-500/10 tw-px-4 tw-py-3 tw-text-sm tw-text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('authenticate') }}" method="post" class="tw-space-y-4 tw-w-full">
                @csrf

                <div class="tw-w-full">
                    <label for="username" class="tw-mb-1.5 tw-block tw-text-xs tw-font-medium tw-uppercase tw-tracking-wide tw-text-slate-400">
                        Username
                    </label>
                    <div class="tw-relative tw-w-full">
                        <span class="tw-pointer-events-none tw-absolute tw-inset-y-0 tw-left-0 tw-z-10 tw-flex tw-w-11 tw-items-center tw-justify-center tw-text-slate-500">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" id="username" name="username" value="{{ old('username') }}"
                            class="tw-login-input @error('username') tw-border-rose-500 @enderror"
                            placeholder="Enter your username" autofocus autocomplete="username" required>
                    </div>
                </div>

                <div class="tw-w-full">
                    <label for="password" class="tw-mb-1.5 tw-block tw-text-xs tw-font-medium tw-uppercase tw-tracking-wide tw-text-slate-400">
                        Password
                    </label>
                    <div class="tw-relative tw-w-full">
                        <span class="tw-pointer-events-none tw-absolute tw-inset-y-0 tw-left-0 tw-z-10 tw-flex tw-w-11 tw-items-center tw-justify-center tw-text-slate-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password"
                            class="tw-login-input"
                            placeholder="Enter your password" autocomplete="current-password" required>
                    </div>
                </div>

                <button type="submit"
                    class="tw-login-submit tw-mt-2 tw-flex tw-w-full tw-items-center tw-justify-center tw-gap-2 tw-rounded-xl tw-border-0 tw-bg-sky-500 tw-px-4 tw-py-3 tw-text-sm tw-font-semibold tw-text-slate-950 tw-transition tw-duration-200 hover:tw-bg-sky-400 hover:tw-shadow-lg hover:tw-shadow-sky-500/25 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-sky-400 focus:tw-ring-offset-2 focus:tw-ring-offset-slate-900">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <p class="tw-mb-0 tw-mt-6 tw-text-center tw-text-sm tw-text-slate-400">
                <a href="{{ route('register') }}"
                    class="tw-inline-flex tw-items-center tw-gap-1.5 tw-text-sky-400 tw-no-underline hover:tw-text-sky-300">
                    <i class="fas fa-user-plus"></i>
                    Register new account
                </a>
            </p>
        </div>

        <p class="tw-mb-0 tw-mt-6 tw-text-center tw-text-xs tw-text-slate-500">
            &copy; {{ date('Y') }} ARKA — AccountingOne
        </p>
    </div>
</body>

</html>
