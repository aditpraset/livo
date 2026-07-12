<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Tutor;

/**
 * Resolusi profil master Tutor milik user yang sedang login — dipakai bersama
 * oleh controller area tutor (web) dan controller API tutor (Sanctum).
 */
trait ResolvesTutor
{
    /**
     * 403 bila akun tutor tidak tertaut ke data master (mis. dibuat manual tanpa tutor_id).
     */
    protected function tutor(): Tutor
    {
        $tutor = auth()->user()?->tutor;
        abort_unless($tutor, 403, 'Akun Anda belum tertaut ke data master tutor. Hubungi admin.');

        return $tutor;
    }
}
