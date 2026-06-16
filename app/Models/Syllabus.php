<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Syllabus extends Model
{
    protected $table = 'syllabi';

    protected $fillable = [
        'subject_id',
        'pokok_bahasan',
        'sub_pokok_bahasan',
        'jenis_kurikulum',
        'kelas',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
