@extends('siswa.layouts.app')

@section('title', 'Jadwal Belajar - LIVO Siswa')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="fs-3 mb-1">Jadwal Belajar</h1>
        <p class="text-muted mb-0">{{ $start->translatedFormat('d M Y') }} — {{ $end->translatedFormat('d M Y') }} · {{ $totalWeek }} sesi</p>
    </div>
    <div class="col-md-6 text-md-end mt-2 mt-md-0">
        <div class="btn-group">
            <a href="{{ route('siswa.schedules.week', ['week' => $prevWeek]) }}" class="btn btn-outline-secondary"><i class="bi bi-chevron-left"></i> Minggu Lalu</a>
            <a href="{{ route('siswa.schedules.week') }}" class="btn btn-outline-primary">Minggu Ini</a>
            <a href="{{ route('siswa.schedules.week', ['week' => $nextWeek]) }}" class="btn btn-outline-secondary">Minggu Depan <i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
</div>

<div class="row g-3">
    @foreach($days as $day)
        @php
            $items = $byDay->get($day->toDateString(), collect());
            $isToday = $day->isToday();
        @endphp
        <div class="col-12">
            <div class="card {{ $isToday ? 'border-primary' : '' }}">
                <div class="card-header bg-white py-2">
                    <h3 class="card-title mb-0 {{ $isToday ? 'text-primary' : '' }}">
                        {{ $day->translatedFormat('l, d M Y') }}
                        @if($isToday)<span class="badge bg-primary ms-2">Hari Ini</span>@endif
                        <span class="text-muted small ms-2">{{ $items->count() }} sesi</span>
                    </h3>
                </div>
                @if($items->isEmpty())
                    <div class="card-body py-3 text-muted small">Tidak ada jadwal.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:130px">Jam</th>
                                    <th style="width:120px">Ruang</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Tutor</th>
                                    <th style="width:110px">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $s)
                                    <tr>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}</span></td>
                                        <td>{{ $s->room ?: '-' }}</td>
                                        <td class="fw-semibold">{{ $s->subject->subject_name ?? '-' }}</td>
                                        <td>{{ $s->tutor->name ?? '-' }}</td>
                                        <td>
                                            @switch($s->status_schedule)
                                                @case('done') <span class="badge bg-success">Selesai</span> @break
                                                @case('canceled') <span class="badge bg-danger">Batal</span> @break
                                                @default <span class="badge bg-info">Terjadwal</span>
                                            @endswitch
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
