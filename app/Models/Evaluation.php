<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    /** Skala huruf (tertinggi → terendah). */
    public const GRADES = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D'];

    /** Konversi nilai huruf → angka. */
    public const SCORES = [
        'A+' => 100, 'A' => 95, 'A-' => 90,
        'B+' => 85,  'B' => 80, 'B-' => 75,
        'C+' => 70,  'C' => 65, 'C-' => 60,
        'D'  => 50,
    ];

    protected $fillable = [
        'schedule_id',
        'syllabus_id',
        'student_attendance',
        'score',
        'pre_test',
        'post_test',
        'pemahaman',
        'poin',
        'kemampuan_analisa',
        'kemampuan_hafalan',
        'kepercayaan_diri',
        'tutor_notes',
        'is_published',
        'quota_consumed',
    ];

    /** Konversi satu nilai huruf ke angka (null bila kosong/tidak dikenal). */
    public static function scoreOf(?string $grade): ?float
    {
        return $grade !== null && isset(self::SCORES[$grade]) ? (float) self::SCORES[$grade] : null;
    }

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
