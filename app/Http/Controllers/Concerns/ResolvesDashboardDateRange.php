<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Rentang tanggal untuk dashboard (Siswa & Administrasi).
 * Default: 1 tahun penuh pada tahun berjalan (1 Jan – 31 Des tahun ini).
 */
trait ResolvesDashboardDateRange
{
    /** @return array{0: Carbon, 1: Carbon} [start, end] — start di 00:00, end di 23:59:59. */
    protected function resolveDashboardRange(Request $request): array
    {
        $now = now();

        $start = $this->parseRangeDate($request->input('start_date')) ?? $now->copy()->startOfYear();
        $end   = $this->parseRangeDate($request->input('end_date')) ?? $now->copy()->endOfYear();

        // Kalau terbalik, tukar supaya query tetap valid.
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->startOfDay(), $end->endOfDay()];
    }

    private function parseRangeDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Daftar bulan (awal bulan) dari $start sampai $end, inklusif.
     * startOfMonth() dipanggil lebih dulu + addMonthNoOverflow() supaya tidak
     * ada bulan yang terlewat/duplikat saat anchor jatuh di tanggal 29–31.
     *
     * @return \Illuminate\Support\Collection<int, Carbon>
     */
    protected function monthsBetween(Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        $months = collect();
        $cursor = $start->copy()->startOfMonth();
        $last   = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $months->push($cursor->copy());
            $cursor->addMonthNoOverflow();
        }

        return $months;
    }
}
