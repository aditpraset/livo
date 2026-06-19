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

    /**
     * Cari harga master berdasarkan kombinasi paket, program, jenjang, dan durasi.
     * Mengembalikan null jika ada field kosong atau kombinasi tidak ditemukan.
     */
    public static function findPrice($packageId, $programId, $gradeId, $duration)
    {
        if (!$packageId || !$programId || !$gradeId || !$duration) {
            return null;
        }

        return static::where('package_id', $packageId)
            ->where('program_id', $programId)
            ->where('grade_id', $gradeId)
            ->where('duration', $duration)
            ->value('price');
    }
}
