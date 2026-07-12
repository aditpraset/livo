<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\ProvisionsUserFromMaster;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

/**
 * Login 2 langkah:
 *  1. User memasukkan email → dicek ke tabel users; bila belum ada, dicari di master
 *     tutor (role tutor) / master siswa (role siswa) lalu akun dibuat otomatis.
 *     Role admin TIDAK di-provision otomatis — datanya murni dari tabel users.
 *  2. Bila user sudah punya password → form password. Bila belum → form buat password.
 */
class LoginController extends Controller implements HasMiddleware
{
    use ProvisionsUserFromMaster;

    public static function middleware(): array
    {
        return [
            new Middleware('guest', except: ['logout']),
            new Middleware('auth', only: ['logout']),
        ];
    }

    /** Step 1: form email, atau step 2 bila email sudah tervalidasi di session. */
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        $email = $request->session()->get('login.email');
        if (!$email) {
            return view('admin.auth.login', ['step' => 'email']);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $request->session()->forget('login.email');
            return view('admin.auth.login', ['step' => 'email']);
        }

        return view('admin.auth.login', [
            'step' => $user->password ? 'password' : 'create-password',
            'email' => $email,
            'name' => $user->name,
        ]);
    }

    /** Step 1 (submit): validasi email, provisioning dari master bila perlu. */
    public function checkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($request->email));

        $user = User::where('email', $email)->first();

        // Belum ada akun → coba provisioning dari data master (tutor lalu siswa).
        if (!$user) {
            $user = $this->provisionFromMaster($email);
        }

        if (!$user) {
            return back()->withInput()->withErrors([
                'email' => 'Email tidak terdaftar. Hubungi admin bila Anda merasa ini keliru.',
            ]);
        }

        if ($user->status === 'nonaktif') {
            return back()->withInput()->withErrors([
                'email' => 'Akun Anda nonaktif. Hubungi admin untuk mengaktifkan kembali.',
            ]);
        }

        // Pastikan role spatie selalu sinkron dengan kolom role.
        $user->syncRoleFromColumn();

        $request->session()->put('login.email', $user->email);

        return redirect()->route('admin.login');
    }

    /** Step 2a: login dengan password (akun yang sudah punya password). */
    public function login(Request $request)
    {
        $email = $request->session()->get('login.email');
        if (!$email) {
            return redirect()->route('admin.login');
        }

        $request->validate(['password' => 'required|string']);

        if (!Auth::attempt(['email' => $email, 'password' => $request->password], $request->boolean('remember'))) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $request->session()->regenerate();
        $request->session()->forget('login.email');

        return $this->redirectByRole(Auth::user());
    }

    /** Step 2b: buat password pertama kali (akun hasil provisioning master). */
    public function createPassword(Request $request)
    {
        $email = $request->session()->get('login.email');
        if (!$email) {
            return redirect()->route('admin.login');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('admin.login');
        }

        // Guard: akun yang sudah punya password tidak boleh menimpa lewat form ini.
        if ($user->password) {
            return redirect()->route('admin.login');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->update([
            'password' => $request->password,
            'status' => 'aktif',
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('login.email');

        return $this->redirectByRole($user);
    }

    /** Kembali ke step email (ganti akun). */
    public function resetEmail(Request $request)
    {
        $request->session()->forget('login.email');
        return redirect()->route('admin.login');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /** Arahkan user sesuai role setelah login. */
    private function redirectByRole(User $user)
    {
        return match (true) {
            $user->hasRole('admin') => redirect()->route('admin.dashboard'),
            $user->hasRole('tutor') => redirect()->route('tutor.dashboard'),
            $user->hasRole('siswa') => redirect()->route('siswa.dashboard'),
            default => redirect()->route('admin.login'),
        };
    }
}
