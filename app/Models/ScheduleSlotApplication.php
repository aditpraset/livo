<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lamaran tutor atas sebuah slot. Satu slot boleh dilamar banyak tutor;
 * saat admin menetapkan satu, lamaran itu `accepted` dan sisanya `rejected`.
 */
class ScheduleSlotApplication extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_ACCEPTED  = 'accepted';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_LABELS = [
        self::STATUS_PENDING   => 'Menunggu Keputusan',
        self::STATUS_ACCEPTED  => 'Terpilih',
        self::STATUS_REJECTED  => 'Tidak Terpilih',
        self::STATUS_WITHDRAWN => 'Dibatalkan Tutor',
    ];

    protected $fillable = [
        'schedule_slot_id',
        'tutor_id',
        'status',
        'note',
        'applied_at',
    ];

    protected $casts = ['applied_at' => 'datetime'];

    public function slot()
    {
        return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id');
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
