<?php

namespace App\Http\Controllers;

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

        if (Auth::attempt($credentials)) {
            // return redirect()->intended('/');
            return redirect()->route('dashboard.index');
        }

        return redirect()->back()->withInput()->withErrors([
            'username' => 'These credentials do not match our records.',
        ]);
    }

    public function logout()
    {
        Auth::logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();

        return redirect('/');
    }
}
