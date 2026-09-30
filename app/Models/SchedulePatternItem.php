<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu baris pola mingguan: hari + sesi + mata pelajaran + group siswa. */
class SchedulePatternItem extends Model
{
    /** Urutan hari, dipakai untuk tampilan matriks & menghitung tanggal dari awal minggu. */
    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    protected $fillable = [
        'schedule_pattern_id',
        'hari',
        'session_id',
        'subject_id',
        'student_group_id',
    ];

    public function pattern()
    {
        return $this->belongsTo(SchedulePattern::class, 'schedule_pattern_id');
    }

    public function session()
    {
        return $this->belongsTo(ScheduleSession::class, 'session_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function studentGroup()
    {
        return $this->belongsTo(StudentGroup::class);
    }

    public function slots()
    {
        return $this->hasMany(ScheduleSlot::class);
    }
}
