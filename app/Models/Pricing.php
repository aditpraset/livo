<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    protected $fillable = [
        'package_id',
        'program_id',
        'grade_id',
        'duration',
        'price',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
}
