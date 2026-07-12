<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Tutor;

abstract class BaseTutorController extends Controller
{
    /**
     * Profil master tutor milik user yang sedang login.
     * 403 bila akun tutor tidak tertaut ke data master (mis. dibuat manual tanpa tutor_id).
     */
    protected function tutor(): Tutor
    {
        $tutor = auth()->user()?->tutor;
        abort_unless($tutor, 403, 'Akun Anda belum tertaut ke data master tutor. Hubungi admin.');

        return $tutor;
    }
}
