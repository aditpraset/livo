<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris pola yang sudah disiapkan untuk minggu tertentu.
 *
 * Alur status:
 *   open ──(tutor melamar, ADMIN menetapkan)──────────► assigned
 *   pending_confirmation ──(TUTOR konfirmasi)─────────► assigned
 *   pending_confirmation ──(tutor mengambil IZIN)─────► vacant
 *   vacant ──(admin menetapkan tutor pengganti)───────► assigned (filled_by_tutor_id)
 *   apa pun ──(admin menutup)──────────────────────────► closed
 *
 * IZIN vs LEPAS: mengambil izin HANYA mengosongkan minggu ini (status jadi
 * `vacant`) — `assigned_tutor_id` tetap menunjuk tutor rutin, jadi pola tetap
 * dibawa ke minggu-minggu berikutnya (lihat petahana di ScheduleSlotController
 * admin::prepare). `filled_by_tutor_id` hanya dipakai untuk mencatat tutor
 * pengganti pada minggu yang kosong tsb, tanpa mengubah kepemilikan pola.
 *
 * Jadwal asli (Schedule) baru dibuat saat status menjadi `assigned`, memakai
 * effectiveTutorId() (pengganti bila ada, kalau tidak tutor rutin).
 */
class ScheduleSlot extends Model
{
    public const STATUS_OPEN                 = 'open';
    public const STATUS_PENDING_CONFIRMATION = 'pending_confirmation';
    public const STATUS_ASSIGNED             = 'assigned';
    public const STATUS_VACANT               = 'vacant';
    public const STATUS_CLOSED               = 'closed';

    /** Label tampilan (nilai simpan => teks di layar). */
    public const STATUS_LABELS = [
        self::STATUS_OPEN                 => 'Tersedia',
        self::STATUS_PENDING_CONFIRMATION => 'Menunggu Konfirmasi',
        self::STATUS_ASSIGNED             => 'Complete',
        self::STATUS_VACANT               => 'Butuh Pengganti',
        self::STATUS_CLOSED               => 'Ditutup',
    ];

    protected $fillable = [
        'schedule_pattern_item_id',
        'week_start',
        'class_date',
        'hari',
        'session_id',
        'subject_id',
        'student_group_id',
        'status',
        'assigned_tutor_id',
        'filled_by_tutor_id',
        'assigned_at',
        'assigned_by',
        'confirmed_at',
        'izin_at',
        'closed_at',
        'closed_by',
        'carried_from_slot_id',
    ];

    protected $casts = [
        'week_start'   => 'date',
        'class_date'   => 'date',
        'assigned_at'  => 'datetime',
        'confirmed_at' => 'datetime',
        'izin_at'      => 'datetime',
        'closed_at'    => 'datetime',
    ];

    public function patternItem()
    {
        return $this->belongsTo(SchedulePatternItem::class, 'schedule_pattern_item_id');
    }

    public function session()
    {
        return $this->belongsTo(ScheduleSession::class, 'session_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function studentGroup()
    {
        return $this->belongsTo(StudentGroup::class);
    }

    public function assignedTutor()
    {
        return $this->belongsTo(Tutor::class, 'assigned_tutor_id');
    }

    /** Tutor pengganti untuk minggu ini saja (diisi bila slot sempat `vacant`). */
    public function filledByTutor()
    {
        return $this->belongsTo(Tutor::class, 'filled_by_tutor_id');
    }

    public function applications()
    {
        return $this->hasMany(ScheduleSlotApplication::class);
    }

    /** Lamaran yang masih menunggu keputusan admin. */
    public function pendingApplications()
    {
        return $this->applications()->where('status', ScheduleSlotApplication::STATUS_PENDING);
    }

    /** Jadwal asli yang lahir dari slot ini. */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /** Slot minggu sebelumnya yang tutornya dibawa ke slot ini. */
    public function carriedFrom()
    {
        return $this->belongsTo(ScheduleSlot::class, 'carried_from_slot_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function needsTutorConfirmation(): bool
    {
        return $this->status === self::STATUS_PENDING_CONFIRMATION;
    }

    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function isVacant(): bool
    {
        return $this->status === self::STATUS_VACANT;
    }

    /** Bisa dilamar/ditetapkan tutor: belum pernah terisi, atau kosong karena izin. */
    public function isOpenForApplication(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_VACANT], true);
    }

    /** Tutor yang benar-benar mengajar: pengganti bila ada, kalau tidak tutor rutin. */
    public function effectiveTutorId(): ?int
    {
        return $this->filled_by_tutor_id ?: $this->assigned_tutor_id;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
