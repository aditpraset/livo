<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Concerns\ResolvesStudent;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class BaseSiswaController extends Controller
{
    use ResolvesStudent;

    /**
     * Query sesi milik siswa yang evaluasinya SUDAH diterbitkan admin.
     * Siswa tidak boleh melihat evaluasi yang masih berstatus draft.
     */
    protected function publishedEvaluationQuery(int $studentId): Builder
    {
        return Schedule::with(['tutor', 'subject', 'evaluation.syllabus'])
            ->where('student_id', $studentId)
            ->whereHas('evaluation', fn ($q) => $q->where('is_published', true));
    }

    /** Hasil query di atas sebagai koleksi, diurutkan dari sesi terbaru. */
    protected function publishedEvaluationSchedules(int $studentId): Collection
    {
        return $this->publishedEvaluationQuery($studentId)
            ->orderBy('class_date', 'desc')->orderBy('start_time', 'desc')
            ->get();
    }
}
