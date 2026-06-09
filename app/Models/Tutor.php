<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    protected $fillable = [
        'name',
        'phone',
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
