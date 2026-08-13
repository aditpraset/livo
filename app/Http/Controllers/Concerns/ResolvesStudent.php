<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Student;

/**
 * Resolusi profil master Siswa milik user yang sedang login — pasangan dari
 * ResolvesTutor, dipakai oleh seluruh controller area siswa.
 */
trait ResolvesStudent
{
    /**
     * 403 bila akun siswa tidak tertaut ke data master (mis. dibuat manual tanpa student_id).
     */
    protected function student(): Student
    {
        $student = auth()->user()?->studentProfile;
        abort_unless($student, 403, 'Akun Anda belum tertaut ke data master siswa. Hubungi admin.');

        return $student;
    }
}
