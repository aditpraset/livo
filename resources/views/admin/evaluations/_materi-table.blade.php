@php
    $vals = array_values(array_filter(array_map(fn($m) => $m['nilai'], $list), fn($v) => $v !== null));
    $total = array_sum($vals);
    $rata  = count($vals) ? round($total / count($vals)) : null;
    $minRows = 6;
@endphp
<div class="section-title">Mata Pelajaran ({{ $title ?? '-' }})</div>
<table class="materi">
    <thead>
        <tr>
            <th class="no">No.</th>
            <th>Materi / Pembahasan</th>
            <th class="val">Nilai Rata-rata</th>
        </tr>
    </thead>
    <tbody>
        @foreach($list as $i => $m)
        <tr>
            <td class="no">{{ $i + 1 }}</td>
            <td>{{ $m['name'] }}</td>
            <td class="val">{{ $m['nilai'] === null ? '-' : number_format($m['nilai'], 0) }}</td>
        </tr>
        @endforeach
        @for($i = count($list); $i < $minRows; $i++)
        <tr>
            <td class="no">{{ $i + 1 }}</td>
            <td>&nbsp;</td>
            <td class="val">&nbsp;</td>
        </tr>
        @endfor
        <tr>
            <td colspan="2" class="lbl-r">Total</td>
            <td class="val">{{ count($list) ? number_format($total, 0) : '' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="lbl-r">Rata-rata</td>
            <td class="val">{{ $rata === null ? '' : number_format($rata, 0) }}</td>
        </tr>
    </tbody>
</table>
