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
        'package_id',
        'pricing_id',
        'program_id',
        'grade_id',
        'duration',
        'program',
        'selected_days',
        'schedule_session_id',
        'class_schedule_ids',
        'school_curriculum',
        'learning_material',
        'promo_code',
        'registration_info',
        'marketing_pic',
        'quota_sessions',
        'photo',
    ];

    protected $casts = [
        'class_schedule_ids' => 'array',
    ];

    /** Program (mata pelajaran) sebagai array, menangani format JSON maupun teks biasa. */
    public function getProgramListAttribute(): array
    {
        if (empty($this->program)) return [];
        $decoded = json_decode($this->program, true);
        if (is_array($decoded)) return array_values(array_filter($decoded));
        return array_values(array_filter(array_map('trim', explode(',', $this->program))));
    }

    /** Program (mata pelajaran) sebagai teks dipisah koma. */
    public function getProgramLabelAttribute(): string
    {
        $list = $this->program_list;
        return count($list) ? implode(', ', $list) : '-';
    }

    /** Jadwal yang dipilih saat pendaftaran (master jadwal) berdasarkan class_schedule_ids. */
    public function getSelectedSchedulesAttribute()
    {
        $ids = $this->class_schedule_ids ?? [];
        if (empty($ids)) {
            return collect();
        }
        return ClassSchedule::with('session')->whereIn('id', $ids)->get();
    }

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

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
