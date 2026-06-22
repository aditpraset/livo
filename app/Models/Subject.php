<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'subject_name',
        'grade_ids',
    ];

    protected $casts = [
        'grade_ids' => 'array',
    ];

    /** Nama-nama jenjang (grades) yang mendapat mata pelajaran ini. */
    public function getGradeNamesAttribute(): array
    {
        $ids = $this->grade_ids ?? [];
        if (empty($ids)) {
            return [];
        }

        return Grade::whereIn('id', $ids)->orderBy('grade_name')->pluck('grade_name')->toArray();
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function syllabi()
    {
        return $this->hasMany(Syllabus::class);
    }
}
