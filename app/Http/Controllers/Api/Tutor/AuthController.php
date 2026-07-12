<?php

namespace App\Http\Controllers\Api\Tutor;

use App\Http\Controllers\Concerns\ProvisionsUserFromMaster;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Login API 2 langkah (setara alur web di App\Http\Controllers\Auth\LoginController,
 * versi stateless bertoken Sanctum):
 *  1. POST /check-email  → cek/provisioning akun dari master Tutor, beri tahu client
 *     apakah harus menampilkan form password atau form buat password.
 *  2a. POST /login          → email + password (akun sudah punya password) → token.
 *  2b. POST /create-password → email + password baru (login pertama kali) → token.
 */
class AuthController extends Controller
{
    use ProvisionsUserFromMaster;

    public function checkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($request->email));

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = $this->provisionFromMaster($email);
        }

        if (!$user || !$user->hasRole('tutor')) {
            throw ValidationException::withMessages([
                'email' => 'Email tidak terdaftar sebagai tutor. Hubungi admin bila Anda merasa ini keliru.',
            ]);
        }

        if ($user->status === 'nonaktif') {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda nonaktif. Hubungi admin untuk mengaktifkan kembali.',
            ]);
        }

        $user->syncRoleFromColumn();

        return response()->json([
            'email' => $user->email,
            'name' => $user->name,
            'has_password' => (bool) $user->password,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user || !$user->hasRole('tutor') || !$user->password || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        if ($user->status === 'nonaktif') {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda nonaktif. Hubungi admin untuk mengaktifkan kembali.',
            ]);
        }

        return $this->respondWithToken($user, $request->input('device_name', 'tutor-app'));
    }

    public function createPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'device_name' => 'nullable|string|max:100',
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user || !$user->hasRole('tutor')) {
            throw ValidationException::withMessages([
                'email' => 'Email tidak terdaftar sebagai tutor.',
            ]);
        }

        // Guard: akun yang sudah punya password tidak boleh menimpa lewat endpoint ini.
        if ($user->password) {
            throw ValidationException::withMessages([
                'email' => 'Akun ini sudah memiliki password. Gunakan endpoint login.',
            ]);
        }

        $user->update([
            'password' => $request->password,
            'status' => 'aktif',
        ]);

        return $this->respondWithToken($user, $request->input('device_name', 'tutor-app'));
    }

    /** Info akun tutor yang sedang login (untuk bootstrap aplikasi client). */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'tutor' => $user->tutor,
        ]);
    }

    /** Cabut token yang sedang dipakai (logout perangkat ini saja). */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    private function respondWithToken(User $user, string $deviceName)
    {
        $user->syncRoleFromColumn();
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'tutor' => $user->tutor,
        ]);
    }
}
