<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'subject_name',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function syllabi()
    {
        return $this->hasMany(Syllabus::class);
    }
}
