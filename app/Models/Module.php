<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Modul belajar (materi) per mata pelajaran + kelas, bisa diunduh tutor. */
class Module extends Model
{
    protected $fillable = [
        'subject_id',
        'kelas',
        'nama_modul',
        'file',
        'file_original_name',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
