<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginBasic extends Controller
{
    /** Tampilkan halaman login (guest) */
    public function index()
    {
        return view('content.authentications.auth-login-basic');
    }

    /** Proses login */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required','string','max:150'],
            'password' => ['required','string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // hanya user aktif yang boleh login
        $remember = (bool) $request->boolean('remember');
        $attempt  = [
            'username'  => $credentials['username'],
            'password'  => $credentials['password'],
            'is_active' => true,
        ];

        if (Auth::attempt($attempt, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard-analytics'));
        }

        throw ValidationException::withMessages([
            'username' => 'Login gagal. Periksa username/password atau akun non-aktif.',
        ]);
    }

    /** Logout */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
