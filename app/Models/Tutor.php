<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    protected $fillable = [
        'name',
        'photo',
        'phone',
        'email',
        'no_rekening',
        'fee_per_session',
        'fee_per_student_private',
        'fee_per_student',
        'fee_transport_per_day',
        'specialization',
    ];

    protected $casts = [
        'specialization' => 'array',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
