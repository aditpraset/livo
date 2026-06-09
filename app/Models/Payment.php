<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'package_id',
        'no_payment',
        'payment_date',
        'expired_date',
        'category_payment',
        'description',
        'amount',
        'amount_paid',
        'payment_method',
        'payment_proof',
        'bank_sender',
        'from',
        'receiver',
        'quota',
        'status_payment',
        'rejection_reason',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
