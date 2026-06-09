<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'package_name',
        'price',
        'total_sessions',
        'description',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
