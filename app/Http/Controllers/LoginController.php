<?php

namespace App\Http\Controllers;

use App\Models\LoginAudit;
use App\Models\User;
use App\Support\LoginThrottle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index()
    {
        return view('login.index');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('username', 'password');
        $usernameAttempt = (string) $request->input('username', '');
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        $user = User::where('username', $credentials['username'] ?? null)->first();

        $successful = $user && $user->is_active && Auth::attempt($credentials);

        LoginAudit::query()->create([
            'user_id' => $successful ? $user->id : ($user?->id),
            'username_dicoba' => $usernameAttempt,
            'berhasil' => $successful,
            'ip' => $ip ?? '',
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
            'created_at' => now(),
        ]);

        if ($successful) {
            LoginThrottle::clearForRequest($request);

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $ip,
            ])->save();

            return redirect()->route('dashboard.index');
        }

        return redirect()->back()->withInput()->withErrors([
            'username' => 'These credentials do not match our records or the account is inactive.',
        ]);
    }

    public function logout()
    {
        Auth::logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();

        return redirect('/login');
    }
}
