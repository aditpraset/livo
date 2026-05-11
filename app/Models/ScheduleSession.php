<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleSession extends Model
{
    protected $fillable = [
        'name',
        'time_start',
        'time_end',
        'notes',
    ];

    public function scheduleStudents()
    {
        return $this->hasMany(ScheduleStudent::class);
    }
}
