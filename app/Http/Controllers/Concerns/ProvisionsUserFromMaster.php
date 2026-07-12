<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Student;
use App\Models\Tutor;
use App\Models\User;

/**
 * Provisioning akun user dari data master berdasarkan email.
 * Tutor → role tutor; Siswa → role siswa. Admin tidak pernah dibuat dari sini
 * (dipakai bersama oleh login web dan login API).
 */
trait ProvisionsUserFromMaster
{
    protected function provisionFromMaster(string $email): ?User
    {
        $tutor = Tutor::where('email', $email)->first();
        if ($tutor) {
            $user = User::create([
                'name' => $tutor->name,
                'email' => $email,
                'password' => null,
                'role' => 'tutor',
                'status' => 'pending',
                'tutor_id' => $tutor->id,
            ]);
            $user->syncRoleFromColumn();
            return $user;
        }

        $student = Student::where('email', $email)->first();
        if ($student) {
            $user = User::create([
                'name' => $student->full_name,
                'email' => $email,
                'password' => null,
                'role' => 'siswa',
                'status' => 'pending',
                'student_id' => $student->id,
            ]);
            $user->syncRoleFromColumn();
            return $user;
        }

        return null;
    }
}
