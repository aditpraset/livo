<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = [
        'schedule_id',
        'student_attendance',
        'score',
        'tutor_notes',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
