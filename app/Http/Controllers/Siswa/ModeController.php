<?php

namespace App\Http\Controllers\Siswa;

use Illuminate\Http\Request;

/**
 * Pemilihan mode masuk area siswa: sebagai **Siswa** atau **Orang Tua**.
 * Keduanya memakai akun & data siswa yang sama — mode hanya menyesuaikan
 * sudut pandang tampilan, disimpan di session dan bisa diganti kapan saja.
 */
class ModeController extends BaseSiswaController
{
    public const SESSION_KEY = 'siswa.mode';

    public const MODES = [
        'siswa' => 'Siswa',
        'orang_tua' => 'Orang Tua',
    ];

    /** Halaman pilihan mode (tampil setelah login, sebelum masuk dashboard). */
    public function show()
    {
        $student = $this->student();
        $current = session(self::SESSION_KEY);

        return view('siswa.mode', compact('student', 'current'));
    }

    public function store(Request $request)
    {
        $this->student(); // pastikan akun tertaut ke master siswa

        $request->validate([
            'mode' => 'required|in:' . implode(',', array_keys(self::MODES)),
        ], [
            'mode.required' => 'Pilih dahulu masuk sebagai Siswa atau Orang Tua.',
            'mode.in' => 'Pilihan mode tidak valid.',
        ]);

        session([self::SESSION_KEY => $request->mode]);

        return redirect()->route('siswa.dashboard');
    }

    /** Kembali ke halaman pilihan untuk berganti mode. */
    public function switch(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('siswa.mode');
    }
}
