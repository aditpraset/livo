<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentRegistration extends Model
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $date = date('ymd');
            $latest = self::where('registration_code', 'like', $date . '%')
                ->orderBy('registration_code', 'desc')
                ->first();

            if ($latest) {
                $sequence = intval(substr($latest->registration_code, 6)) + 1;
            } else {
                $sequence = 1;
            }

            $model->registration_code = $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            
            if (empty($model->status)) {
                $model->status = 'Baru';
            }
        });
    }

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

    public function scheduleSession()
    {
        return $this->belongsTo(ScheduleSession::class);
    }
}
