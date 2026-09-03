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
}
