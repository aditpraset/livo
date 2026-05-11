<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_code',
        'nis',
        'status',
        'registration_date',
        'full_name',
        'nickname',
        'birth_date',
        'religion',
        'gender',
        'grade',
        'school_origin',
        'father_name',
        'mother_name',
        'guardian_name',
        'address',
        'email',
        'phone',
        'whatsapp',
        'class_type',
        'kbm_process',
        'package',
        'program',
        'selected_days',
        'schedule_session_id',
        'school_curriculum',
        'learning_material',
        'promo_code',
        'registration_info',
        'marketing_pic',
    ];

    public function scheduleSession()
    {
        return $this->belongsTo(ScheduleSession::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scheduleStudents()
    {
        return $this->hasMany(ScheduleStudent::class);
    }
}
