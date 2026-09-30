<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    /** Kategori tutor (nilai simpan => label tampilan). */
    public const KATEGORI_OPTIONS = [
        'freelance' => 'Freelance',
        'tetap'     => 'Tetap',
    ];

    protected $fillable = [
        'name',
        'photo',
        'phone',
        'email',
        'no_rekening',
        'fee_per_session',
        'fee_per_student_private',
        'fee_per_student',
        'fee_transport_per_day',
        'fee_private_khusus',
        'fee_tka_regular',
        'fee_private_sma',
        'fee_tka_visit',
        'specialization',
        'kategori',
        'gaji_pokok',
        'tunjangan_per_bulan',
        'maks_sesi_tunjangan_per_bulan',
    ];

    protected $casts = [
        'specialization' => 'array',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function tutorFees()
    {
        return $this->hasMany(TutorFee::class);
    }

    /**
     * Apakah tutor ini memenuhi kualifikasi mengajar mapel tsb?
     * Kualifikasi disimpan di `specialization` sebagai array NAMA mapel.
     */
    public function isQualifiedFor(?Subject $subject): bool
    {
        if (!$subject) {
            return false;
        }

        $specs = is_array($this->specialization) ? $this->specialization : [];

        return in_array($subject->subject_name, $specs, true);
    }

    /** Tutor yang berkualifikasi untuk suatu nama mata pelajaran. */
    public function scopeQualifiedFor($query, ?string $subjectName)
    {
        if (!$subjectName) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereJsonContains('specialization', $subjectName);
    }
}
