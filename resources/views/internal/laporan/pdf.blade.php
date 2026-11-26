<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Permohonan</title>
<style>
    @page { margin: 18mm 12mm 16mm 12mm; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 8pt; color: #0f172a; }
    h1 { font-size: 13pt; margin: 0 0 2px; color: #0f2a4a; }
    .sub { font-size: 9pt; color: #475569; margin: 0 0 10px; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 1px 6px 1px 0; font-size: 8pt; }
    .meta td:first-child { color: #64748b; width: 110px; }
    .data { margin-top: 10px; }
    .data th { background: #0f2a4a; color: #fff; font-weight: bold; text-align: left; padding: 4px; font-size: 7.5pt; }
    .data td { border-bottom: 0.5pt solid #e2e8f0; padding: 3px 4px; vertical-align: top; }
    .data tr:nth-child(even) td { background: #f8fafc; }
    .ringkasan { width: auto; margin-top: 12px; }
    .ringkasan th, .ringkasan td { border: 0.5pt solid #cbd5e1; padding: 3px 8px; }
    .ringkasan th { background: #ecfdf5; text-align: left; }
    .num, .ringkasan .num { text-align: right; }
    .muted { color: #94a3b8; }
    .lengkap { color: #047857; }
    .tidak { color: #b91c1c; }
    .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7pt; color: #94a3b8; }
    .nowrap { white-space: nowrap; }
    .ringkasan { page-break-inside: avoid; }
</style>
</head>
<body>
    <div class="footer">
        Dicetak {{ $dicetak }} oleh {{ $user->nama }}
    </div>

    <h1>Laporan Pengajuan Rancangan Denah PBF</h1>
    <p class="sub">{{ $user->isKetuaTim() ? 'Permohonan tim Ketua Tim' : 'Permohonan yang dievaluasi' }}: {{ $user->nama }}</p>

    <table class="meta">
        @foreach($keterangan as [$label, $nilai])
            <tr><td>{{ $label }}</td><td>{{ $nilai }}</td></tr>
        @endforeach
        <tr><td>Jumlah permohonan</td><td><strong>{{ count($baris) }}</strong></td></tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>No. Registrasi</th>
                <th>Nama PBF</th>
                <th>Tgl Pengajuan</th>
                <th>Jenis</th>
                <th>Status</th>
                <th>Revisi</th>
                @if($tampilEvaluator ?? true)<th>Evaluator</th>@endif
                <th>Evaluasi Terakhir</th>
                <th>No. Surat / Terbit</th>
                <th>SLA</th>
            </tr>
        </thead>
        <tbody>
            @forelse($baris as $b)
                <tr>
                    <td class="num">{{ $b[0] }}</td>
                    <td class="nowrap">{{ $b[1] }}</td>
                    <td>{{ $b[2] }}<br><span class="muted">NIB {{ $b[3] ?: '-' }}</span></td>
                    <td class="nowrap">{{ $b[4] }}</td>
                    <td>{{ $b[5] }}</td>
                    <td>{{ $b[6] }}</td>
                    <td>{{ $b[7] }}</td>
                    @if($tampilEvaluator ?? true)<td>{{ $b[9] ?: '-' }}</td>@endif
                    <td>
                        @if($b[11])
                            <span class="{{ $b[11] === 'Lengkap' ? 'lengkap' : 'tidak' }}">{{ $b[11] }}</span><br><span class="muted">{{ $b[10] }}</span>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td class="nowrap">{{ $b[12] ?: '-' }}@if($b[13])<br><span class="muted">{{ $b[13] }}</span>@endif</td>
                    <td>{{ $b[14] ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ ($tampilEvaluator ?? true) ? 11 : 10 }}" class="muted" style="text-align:center; padding:12px">Tidak ada permohonan yang cocok dengan filter.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="ringkasan">
        <thead><tr><th>Status</th><th class="num">Jumlah</th></tr></thead>
        <tbody>
            @foreach($ringkasan as $r)
                <tr><td>{{ $r['label'] }}</td><td class="num">{{ $r['jumlah'] }}</td></tr>
            @endforeach
            <tr><th>Total</th><th class="num">{{ count($baris) }}</th></tr>
        </tbody>
    </table>
</body>
</html>
