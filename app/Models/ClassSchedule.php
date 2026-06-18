<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    protected $fillable = [
        'session_id',
        'program_id',
        'hari',
        'kelas',
    ];

    public function session()
    {
        return $this->belongsTo(ScheduleSession::class, 'session_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
