<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Rincian fee satu tutor pada satu periode (hasil generate, breakdown a+b+c+d). */
class TutorFee extends Model
{
    protected $fillable = [
        'fee_period_id',
        'tutor_id',
        'private_count',
        'regular_count',
        'session_count',
        'day_count',
        'fee_private',
        'fee_regular',
        'fee_session',
        'fee_transport',
        'total',
    ];

    public function period()
    {
        return $this->belongsTo(FeePeriod::class, 'fee_period_id');
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }
}
