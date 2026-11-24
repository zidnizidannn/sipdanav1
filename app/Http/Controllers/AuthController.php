<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        $demoUsers = app()->environment('local')
            ? User::orderBy('id')->get()
            : collect();

        return view('auth.login', compact('demoUsers'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('proposals.index'));
        }

        return back()
            ->withErrors(['email' => 'Email atau password tidak sesuai.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Ganti mode khusus akun Yayasan: Pemberi Dana <-> Pengaju.
     */
    public function toggleYayasanMode(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->role_code === 'YAYASAN', 403, 'Fitur ini khusus akun Yayasan.');

        $newMode = $user->yayasan_mode === 'FUND_PROVIDER' ? 'APPLICANT' : 'FUND_PROVIDER';
        $user->update(['yayasan_mode' => $newMode]);

        $label = $newMode === 'FUND_PROVIDER' ? 'Pemberi Dana (Unit Sekolah)' : 'Pengaju (ke Pusat)';

        return redirect()
            ->route('proposals.index')
            ->with('success', "Mode Yayasan diubah ke: {$label}.");
    }

    /**
     * Quick Switch akun khusus mode lokal (development) untuk uji coba multi-role.
     */
    public function switchRole(Request $request)
    {
        abort_unless(app()->environment('local'), 403, 'Hanya tersedia di lingkungan lokal.');

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        Auth::loginUsingId($validated['user_id']);
        $request->session()->regenerate();

        return redirect()
            ->route('proposals.index')
            ->with('success', 'Berhasil berpindah ke akun: ' . Auth::user()->name);
    }
}