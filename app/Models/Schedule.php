<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'student_id',
        'tutor_id',
        'subject_id',
        'room',
        'class_date',
        'start_time',
        'end_time',
        'status_schedule',
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
}
