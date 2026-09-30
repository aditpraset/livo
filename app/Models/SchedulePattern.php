<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pola jadwal mingguan — template yang dipakai berulang tiap minggu.
 * Isinya baris-baris (hari + sesi + mapel + group siswa) di SchedulePatternItem.
 */
class SchedulePattern extends Model
{
    protected $fillable = ['name', 'is_active', 'notes'];

    protected $casts = ['is_active' => 'boolean'];

    public function items()
    {
        return $this->hasMany(SchedulePatternItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
