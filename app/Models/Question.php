<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'syllabus_id',
        'question',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
    ];

    public function syllabus()
    {
        return $this->belongsTo(Syllabus::class);
    }

    /** Keempat pilihan sebagai array ['a' => ..., 'b' => ..., 'c' => ..., 'd' => ...]. */
    public function getOptionsAttribute(): array
    {
        return [
            'a' => $this->option_a,
            'b' => $this->option_b,
            'c' => $this->option_c,
            'd' => $this->option_d,
        ];
    }

    /** Teks pilihan jawaban yang benar. */
    public function getCorrectOptionTextAttribute(): ?string
    {
        return $this->options[$this->correct_answer] ?? null;
    }
}
