<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'no_payment',
        'payment_date',
        'expired_date',
        'category_payment',
        'description',
        'amount',
        'payment_method',
        'from',
        'receiver',
        'quota',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
