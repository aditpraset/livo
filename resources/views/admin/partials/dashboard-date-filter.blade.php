{{--
    Filter rentang tanggal untuk dashboard (Siswa & Administrasi).
    Butuh: $filterRoute (nama route), $rangeStart, $rangeEnd (Carbon).
    Default rentang: 1 tahun penuh tahun berjalan (di-resolve di controller).
--}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route($filterRoute) }}" class="row g-3 align-items-end" id="dashboard-date-filter">
            {{-- Pertahankan query lain (mis. week) saat menerapkan filter tanggal --}}
            @foreach(request()->except(['start_date', 'end_date', 'page']) as $k => $v)
                @if(is_scalar($v))
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach

            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-semibold small mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $rangeStart->toDateString() }}">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label fw-semibold small mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control" value="{{ $rangeEnd->toDateString() }}">
            </div>
            <div class="col-md-auto col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Terapkan</button>
                <a href="{{ route($filterRoute) }}" class="btn btn-outline-secondary">Reset</a>
            </div>

            <div class="col-12">
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Menampilkan data <strong>{{ $rangeStart->translatedFormat('d M Y') }} – {{ $rangeEnd->translatedFormat('d M Y') }}</strong>
                    &middot; default 1 tahun penuh tahun berjalan.
                </span>
            </div>
        </form>
    </div>
</div>
