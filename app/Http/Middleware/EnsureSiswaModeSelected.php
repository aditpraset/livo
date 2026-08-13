<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Siswa\ModeController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman area siswa baru bisa diakses setelah user memilih mode masuk
 * (Siswa / Orang Tua). Bila belum memilih, diarahkan ke halaman pilihan.
 *
 * Mode yang aktif dibagikan ke seluruh view area siswa sebagai $siswaMode &
 * $siswaModeLabel — layout maupun view anak sama-sama membutuhkannya.
 */
class EnsureSiswaModeSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = (string) $request->session()->get(ModeController::SESSION_KEY);

        if (!array_key_exists($mode, ModeController::MODES)) {
            return redirect()->route('siswa.mode');
        }

        View::share([
            'siswaMode' => $mode,
            'siswaModeLabel' => ModeController::MODES[$mode],
        ]);

        return $next($request);
    }
}
