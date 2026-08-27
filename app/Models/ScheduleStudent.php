<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleStudent extends Model
{
    protected $fillable = [
        'student_id',
        'schedule_session_id',
        'date',
        'notes',
    ];

    /** withTrashed: siswa yang sudah dihapus (soft-delete) tetap tampil di riwayat. */
    public function student()
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function scheduleSession()
    {
        return $this->belongsTo(ScheduleSession::class);
    }
}
