<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = [
        'schedule_id',
        'syllabus_id',
        'materi_manual',
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

    /**
     * Materi yang ditampilkan: dari silabus bila dipilih, atau materi manual ("Lainnya").
     * Mengembalikan ['pokok' => ..., 'sub' => ...] agar tampilan tetap bertingkat.
     */
    public function getMateriDisplayAttribute(): ?array
    {
        if ($this->syllabus) {
            return [
                'pokok' => $this->syllabus->pokok_bahasan,
                'sub'   => $this->syllabus->sub_pokok_bahasan,
            ];
        }

        if (!empty($this->materi_manual)) {
            return ['pokok' => $this->materi_manual, 'sub' => null];
        }

        return null;
    }

    /** Materi sebagai satu baris teks (untuk Excel/PDF). */
    public function getMateriTextAttribute(): ?string
    {
        $m = $this->materi_display;
        if (!$m) {
            return null;
        }
        return trim($m['pokok'] . ($m['sub'] ? ' — ' . $m['sub'] : ''));
    }
}
