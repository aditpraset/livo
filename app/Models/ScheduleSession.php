<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleSession extends Model
{
    protected $fillable = [
        'name',
        'time_start',
        'time_end',
        'notes',
    ];

    public function scheduleStudents()
    {
        return $this->hasMany(ScheduleStudent::class);
    }

    /** Warna fallback utk sesi yang tidak dikenali (mis. jam custom di luar sesi master). */
    public static function defaultColor(): array
    {
        return ['bg' => '#e9ecef', 'text' => '#495057', 'border' => '#ced4da'];
    }

    /**
     * Peta warna tetap & identik per sesi (`id` => [bg, text, border]) — dipakai di
     * seluruh tampilan jadwal (tab Grup Sesi admin, jadwal mingguan tutor, dst) supaya
     * satu sesi SELALU tampil dengan warna yang sama, berapa pun jumlah sesi yang ada.
     *
     * Warna dihasilkan dari id sesi via rotasi golden-angle (137.508°) di ruang HSL —
     * bukan dari daftar warna tetap — supaya warnanya tetap unik & tersebar rata walau
     * jumlah sesi terus bertambah, dan warna sesi yang sudah ada tidak pernah berubah
     * ketika sesi baru ditambahkan/dihapus (hue hanya bergantung pada id sesi itu sendiri).
     */
    public static function colorMap(): \Illuminate\Support\Collection
    {
        return static::pluck('id')->mapWithKeys(function (int $id) {
            $hue = fmod($id * 137.508, 360);

            return [$id => [
                'bg'     => sprintf('hsl(%.1f, 70%%, 92%%)', $hue),
                'text'   => sprintf('hsl(%.1f, 65%%, 32%%)', $hue),
                'border' => sprintf('hsl(%.1f, 55%%, 78%%)', $hue),
            ]];
        });
    }
}
