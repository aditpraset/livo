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
        'active_date',
        'period',
        'expired_date',
        'masa_aktif',
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

    /** withTrashed: siswa yang sudah dihapus (soft-delete) tetap tampil di riwayat pembayaran. */
    public function student()
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
