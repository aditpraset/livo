<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Preset grouping siswa (nama + Sesi + Hari), dipakai saat membuat jadwal per grouping. */
class StudentGroup extends Model
{
    protected $fillable = [
        'name',
        'session_id',
        'hari',
    ];

    public function session()
    {
        return $this->belongsTo(ScheduleSession::class, 'session_id');
    }

    /** Anggota eksplisit group ini — dipakai langsung (tanpa pencocokan otomatis) saat generate jadwal per grouping. */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_group_student');
    }
}
