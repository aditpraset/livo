<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu periode (bulan) fee tutor yang di-generate admin: draft → published. */
class FeePeriod extends Model
{
    protected $fillable = [
        'month',
        'status',
        'generated_at',
        'generated_by',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'month'        => 'date',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function tutorFees()
    {
        return $this->hasMany(TutorFee::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** Cari periode published untuk bulan tertentu (null bila belum ada/belum terbit). */
    public static function publishedFor(\Carbon\Carbon $month): ?self
    {
        return static::where('month', $month->copy()->startOfMonth()->toDateString())
            ->where('status', 'published')
            ->first();
    }
}
