<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = [
        'schedule_id',
        'syllabus_id',
        'student_attendance',
        'score',
        'post_test',
        'pemahaman',
        'kemampuan_analisa',
        'kemampuan_hafalan',
        'kepercayaan_diri',
        'tutor_notes',
        'is_published',
        'quota_consumed',
    ];

    protected $casts = [
        'is_published'   => 'boolean',
        'quota_consumed' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function syllabus()
    {
        return $this->belongsTo(Syllabus::class);
    }
}
