<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    /** Pilihan feedback siswa (nilai simpan => label tampilan). Melekat pada sesi,
     *  bisa diisi tutor kapan saja — termasuk sebelum sesi ini dievaluasi. */
    public const FEEDBACK_OPTIONS = [
        'buruk'       => 'Buruk',
        'kurang_baik' => 'Kurang Baik',
        'cukup_baik'  => 'Cukup Baik',
        'baik'        => 'Baik',
        'sangat_baik' => 'Sangat Baik',
    ];

    protected $fillable = [
        'student_id',
        'tutor_id',
        'subject_id',
        'room',
        'class_date',
        'start_time',
        'end_time',
        'status_schedule',
        'student_feedback',
    ];

    protected $casts = [
        'class_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function evaluation()
    {
        return $this->hasOne(Evaluation::class);
    }

    /** Label tampilan feedback siswa (mis. "Sangat Baik"), null bila belum diisi. */
    public function getStudentFeedbackLabelAttribute(): ?string
    {
        return self::FEEDBACK_OPTIONS[$this->student_feedback] ?? null;
    }
}
