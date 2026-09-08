@extends('layouts.internal')

@section('title', 'Dashboard')
@section('content')
<?php $pageTitle = 'Dashboard Kepala Balai'; ?>

@php
$statuses = [
    'pengajuan',
    'didisposisikan',
    'proses_evaluasi',
    'revisi_1',
    'revisi_2',
    'revisi_3',
    'menunggu_surat_pengesahan',
    'terbit_surat_pengesahan',
    'ditutup_pengajuan_ulang',
];

$roleBucket = [
    'kabalai' => [],
    'katim'   => [],
    'staff'   => [],
    'pemohon' => [],
];
foreach ($permohonans as $p) {
    $s = $p->status_saat_ini;
    if ($s === 'pengajuan') {
        // Kabalai: input permohonan, belum didisposisikan
        $roleBucket['kabalai'][] = $p;
    } elseif ($s === 'didisposisikan') {
        // Katim: sudah didisposisikan kabalai, belum didistribusikan ke staff
        $roleBucket['katim'][] = $p;
    } elseif (in_array($s, ['proses_evaluasi', 'menunggu_surat_pengesahan'])) {
        // Staff: sudah didistribusikan, sedang diproses
        $roleBucket['staff'][] = $p;
    } elseif ($s === 'ditutup_pengajuan_ulang') {
        // Ditutup: staff menutup karena revisi ke-3 gagal
        $roleBucket['staff'][] = $p;
    } elseif (in_array($s, ['revisi_1', 'revisi_2', 'revisi_3'])) {
        // Revisi: menunggu aksi dari pemohon
        $roleBucket['pemohon'][] = $p;
    } elseif ($s === 'terbit_surat_pengesahan') {
        // Terbit: pemohon mendapatkan surat pengesahan
        $roleBucket['pemohon'][] = $p;
    }
}

$counts = $permohonans->countBy('status_saat_ini');
$namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
@endphp

{{-- Statistik Permohonan (paling atas) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-blue-100 uppercase tracking-wide">Total {{ $selectedYear }}</p>
        <p class="text-2xl font-bold mt-1">{{ $statBulanan->sum('total') }}</p>
        <p class="text-xs text-blue-200 mt-1">permohonan masuk</p>
    </div>
    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-emerald-100 uppercase tracking-wide">Terbit</p>
        <p class="text-2xl font-bold mt-1">{{ $statBulanan->sum('terbit') }}</p>
        <p class="text-xs text-emerald-200 mt-1">surat pengesahan</p>
    </div>
    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-amber-100 uppercase tracking-wide">On Process</p>
        <p class="text-2xl font-bold mt-1">{{ $onProcess }}</p>
        <p class="text-xs text-amber-200 mt-1">sedang diproses</p>
    </div>
</div>

@if($statBulanan->isNotEmpty())
@php
$labels = $statBulanan->pluck('bulan')->map(fn($b) => $namaBulan[(int)(explode('-',$b)[1] ?? 1) - 1] ?? $b)->toArray();
$totalData = $statBulanan->pluck('total')->map(fn($v) => (int)$v)->toArray();
$terbitData = $statBulanan->pluck('terbit')->map(fn($v) => (int)$v)->toArray();

$maxVal = max(array_merge($totalData, $terbitData, [1]));
$w = 800; $h = 220;
$padL = 36; $padR = 24; $padT = 16; $padB = 36;
$plotW = $w - $padL - $padR;
$plotH = $h - $padT - $padB;
$ptCount = count($labels);
$barW = $ptCount > 0 ? min(max(($plotW / $ptCount) * 0.30, 6), 28) : 28;
$groupW = $ptCount > 0 ? ($plotW - $barW * 2 * $ptCount) / max($ptCount - 1, 1) : 0;
$stepX = $barW * 2 + $groupW;
@endphp

<x-ui.card class="mb-4">
    <div class="flex items-center justify-between p-6 pb-4">
        <h3 class="font-semibold text-lg leading-none tracking-tight text-blue-900">Statistik Per Bulan</h3>
        <form method="GET" action="" class="flex items-center gap-2">
            <select name="tahun" onchange="this.form.submit()"
                class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 text-slate-600 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $y === $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <x-ui.card-content>
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            {{-- Grafik Bar Chart SVG --}}
            <div class="xl:col-span-2" x-data="{
                tooltip: null, ttX: 0, ttY: 0,
                show(bulan, idx, mx, my) {
                    this.tooltip = bulan;
                    this.ttX = mx; this.ttY = my;
                },
                hide() { this.tooltip = null; }
            }">
                <div class="relative" style="width:100%;max-width:800px">
                    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full" style="overflow:visible"
                        @mousemove.prevent="let r=$refs.grafik.getBoundingClientRect(),m=$refs.grafik.createSVGPoint();m.x=$event.clientX;m.y=$event.clientY;let p=m.matrixTransform($refs.grafik.getScreenCTM().inverse());let idx=Math.round((p.x-{{ $padL }})/{{ $stepX }});idx=Math.max(0,Math.min(idx,{{ $ptCount-1 }}));let bx={{ $padL }}+idx*{{ $stepX }}+{{ $barW/2 }};this.show(['{{ implode("','", $labels) }}'][idx],idx,((bx/{{ $w }})*100+'%'),((p.y/{{ $h }})*100+'%'))"
                        @mouseleave="hide()">
                        <g x-ref="grafik">
                            {{-- gridlines --}}
                            @for($i=0;$i<=4;$i++)
                                @php $gy = $padT + ($plotH/4)*$i; $gv = $maxVal - ($maxVal/4)*$i; @endphp
                                <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $w-$padR }}" y2="{{ $gy }}" stroke="#e1e0d9" stroke-width="1"/>
                                <text x="{{ $padL - 6 }}" y="{{ $gy + 4 }}" text-anchor="end" font-size="11" fill="#898781" font-family="system-ui,sans-serif">{{ round($gv) }}</text>
                            @endfor
                            {{-- bars --}}
                            @foreach($labels as $i => $lbl)
                                @php
                                    $xBase = $padL + $i * ($barW * 2 + $groupW);
                                    $barTotalH = $maxVal > 0 ? ($totalData[$i] / $maxVal) * $plotH : 0;
                                    $barTerbitH = $maxVal > 0 ? ($terbitData[$i] / $maxVal) * $plotH : 0;
                                    $barY = $plotH + $padT;
                                @endphp
                                {{-- Total bar --}}
                                <rect x="{{ $xBase }}" y="{{ $barY - $barTotalH }}" width="{{ $barW }}" height="{{ $barTotalH }}" rx="3" fill="#256abf" class="hover:opacity-80 transition-opacity cursor-pointer" @mouseenter="show('{{ $lbl }}', {{ $i }}, 0, 0)" @mouseleave="hide()"/>
                                {{-- Terbit bar --}}
                                <rect x="{{ $xBase + $barW }}" y="{{ $barY - $barTerbitH }}" width="{{ $barW }}" height="{{ $barTerbitH }}" rx="3" fill="#059669" class="hover:opacity-80 transition-opacity cursor-pointer" @mouseenter="show('{{ $lbl }}', {{ $i }}, 0, 0)" @mouseleave="hide()"/>
                                {{-- x-axis label --}}
                                <text x="{{ $xBase + $barW }}" y="{{ $h - 8 }}" text-anchor="middle" font-size="11" fill="#898781" font-family="system-ui,sans-serif">{{ $lbl }}</text>
                            @endforeach
                        </g>
                        {{-- tooltip --}}
                        <template x-if="tooltip !== null">
                            <g>
                                <rect x="0" y="0" width="160" height="62" fill="white" stroke="#e1e0d9" stroke-width="1" rx="6" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.1))"/>
                                <text x="10" y="20" font-size="12" font-weight="600" fill="#0b0b0b" font-family="system-ui,sans-serif" x-text="tooltip"></text>
                                <template x-for="(row, idx) in [['Total','#256abf',{{ json_encode($totalData) }}[tooltip?.[1]]],['Terbit','#059669',{{ json_encode($terbitData) }}[tooltip?.[1]]]]" :key="idx">
                                    <g>
                                        <rect :x="10" :y="28+idx*14" width="8" :height="8" rx="2" :fill="row[1]"/>
                                        <text x="24" y="36+idx*14" font-size="11" fill="#52514e" font-family="system-ui,sans-serif" x-text="row[0]"></text>
                                        <text x="150" y="36+idx*14" text-anchor="end" font-size="11" font-weight="600" fill="#0b0b0b" font-family="system-ui,sans-serif" x-text="row[2] ?? 0"></text>
                                    </g>
                                </template>
                            </g>
                        </template>
                    </svg>
                </div>
                {{-- Legend --}}
                <div class="flex items-center gap-6 mt-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 rounded" style="background:#256abf"></span>
                        <span class="text-xs text-slate-600">Total</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 rounded" style="background:#059669"></span>
                        <span class="text-xs text-slate-600">Terbit</span>
                    </div>
                </div>
            </div>
            {{-- Tabel data --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500 uppercase">Bulan</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500 uppercase">Total</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500 uppercase">Terbit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($statBulanan as $s)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-3 py-2 text-sm font-medium text-slate-700">
                                {{ $namaBulan[(int)(explode('-',$s->bulan)[1] ?? 1) - 1] ?? $s->bulan }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="inline-flex items-center justify-center min-w-[24px] h-5 rounded-full bg-blue-100 px-1.5 text-xs font-semibold text-blue-800">{{ $s->total }}</span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="inline-flex items-center justify-center min-w-[24px] h-5 rounded-full bg-emerald-100 px-1.5 text-xs font-semibold text-emerald-800">{{ $s->terbit }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-ui.card-content>
</x-ui.card>
@endif

{{-- Keterangan Role & Status (2 kolom) --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    {{-- Keterangan Role --}}
    <x-ui.card>
        <x-ui.card-header title="Keterangan Role" />
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Kepala Balai</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = count($roleBucket['kabalai']); @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-blue-100 px-2 text-xs font-semibold text-blue-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Ketua Tim Sertifikasi</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = count($roleBucket['katim']); @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-purple-100 px-2 text-xs font-semibold text-purple-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Staff Sertifikasi</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = count($roleBucket['staff']); @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-cyan-100 px-2 text-xs font-semibold text-cyan-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Pemohon (PBF)</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = count($roleBucket['pemohon']); @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-amber-100 px-2 text-xs font-semibold text-amber-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- Keterangan Status --}}
    <x-ui.card>
        <x-ui.card-header title="Keterangan Status" />
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Pengajuan</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['pengajuan'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-blue-100 px-2 text-xs font-semibold text-blue-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Didiposisisikan</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['didisposisikan'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-purple-100 px-2 text-xs font-semibold text-purple-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Proses Evaluasi</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['proses_evaluasi'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-cyan-100 px-2 text-xs font-semibold text-cyan-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Revisi 1</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['revisi_1'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-amber-100 px-2 text-xs font-semibold text-amber-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Revisi 2</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['revisi_2'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-amber-100 px-2 text-xs font-semibold text-amber-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Revisi 3</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['revisi_3'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-amber-100 px-2 text-xs font-semibold text-amber-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Menunggu Surat</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['menunggu_surat_pengesahan'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-violet-100 px-2 text-xs font-semibold text-violet-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Terbit Surat</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['terbit_surat_pengesahan'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-emerald-100 px-2 text-xs font-semibold text-emerald-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 text-sm text-slate-700 font-medium">Ditutup</td>
                        <td class="px-4 py-2.5 text-right">
                            @php $j = $counts['ditutup_pengajuan_ulang'] ?? 0; @endphp
                            @if($j > 0)
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-red-100 px-2 text-xs font-semibold text-red-800">{{ $j }}</span>
                            @else
                                <span class="text-slate-400">0</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>

{{-- Permohonan --}}
<div class="flex items-center justify-between mb-3">
    <h2 class="text-base font-semibold text-blue-900">Permohonan</h2>
    <x-ui.button variant="default" size="sm" href="{{ route('internal.kabalai.permohonan.create') }}">
        <i class="ph ph-plus" aria-hidden="true"></i> Input Permohonan
    </x-ui.button>
</div>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No. Reg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">PBF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">SLA</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>
            @forelse($permohonans->take(10) as $p)
            <x-ui.permohonan-row :permohonan="$p" :colspan="6">
                <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $p->nama_pbf_snapshot }}</td>
                <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                <td class="px-4 py-3"><x-ui.sla-badge :sla="app(\App\Services\SlaCalculator::class)->evaluasiPermohonan($p)" /></td>
                <td class="px-4 py-3 text-slate-400 text-xs">{{ $p->tanggal_pengajuan?->format('d M Y') }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.permohonan.show', $p) }}">Detail</x-ui.button>
                    @if($p->status_saat_ini === 'pengajuan')
                        <x-ui.button variant="ghost" size="sm" href="{{ route('internal.kabalai.permohonan.edit', $p) }}" class="!text-amber-600 hover:!text-amber-700">
                            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                        </x-ui.button>
                        <form action="{{ route('internal.kabalai.permohonan.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus permohonan ini? Dokumen dan log terkait juga akan ikut dihapus.')">
                            @csrf
                            @method('DELETE')
                            <x-ui.button variant="ghost" size="sm" type="submit" class="!text-red-500 hover:!text-red-700">
                                <i class="ph ph-trash" aria-hidden="true"></i>
                            </x-ui.button>
                        </form>
                    @endif
                    <x-ui.timeline-toggle />
                </td>
            </x-ui.permohonan-row>
            @empty
            <tbody>
                <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">Belum ada permohonan.</td></tr>
            </tbody>
            @endforelse
        </table>
        </div>
    </x-ui.card-content>
</x-ui.card>
@endsection
