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
        <p class="text-xs font-medium text-blue-100 uppercase tracking-wide">Total {{ now()->year }}</p>
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
$ditutupData = $statBulanan->pluck('ditutup')->map(fn($v) => (int)$v)->toArray();

$maxVal = max(array_merge($totalData, $terbitData, $ditutupData, [1]));
$w = 800; $h = 220;
$padL = 36; $padR = 24; $padT = 16; $padB = 36;
$plotW = $w - $padL - $padR;
$plotH = $h - $padT - $padB;
$ptCount = count($labels);
$stepX = $ptCount > 1 ? $plotW / ($ptCount - 1) : 0;

function pt($v, $maxV, $h, $padT) { return $h - $padT - ($maxV > 0 ? ($v / $maxV) * $h : 0); }
function polyline($data, $maxV, $stepX, $plotH, $padL, $padT) {
    $pts = [];
    foreach ($data as $i => $v) { $pts[] = ($padL + $i * $stepX) . ',' . pt($v, $maxV, $plotH, $padT); }
    return implode(' ', $pts);
}
function areaPts($data, $maxV, $stepX, $plotH, $padL, $padT, $padB) {
    $pts = [];
    foreach ($data as $i => $v) { $pts[] = ($padL + $i * $stepX) . ',' . pt($v, $maxV, $plotH, $padT); }
    $last = count($data) - 1;
    $pts[] = ($padL + $last * $stepX) . ',' . ($plotH + $padT);
    $pts[] = $padL . ',' . ($plotH + $padT);
    return implode(' ', $pts);
}
@endphp

<x-ui.card class="mb-4">
    <x-ui.card-header title="Statistik Per Bulan {{ now()->year }}" />
    <x-ui.card-content>
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            {{-- Grafik Line Chart SVG --}}
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
                        @mousemove.prevent="let r=$refs.grafik.getBoundingClientRect(),m=$refs.grafik.createSVGPoint();m.x=$event.clientX;m.y=$event.clientY;let p=m.matrixTransform($refs.grafik.getScreenCTM().inverse());let idx=Math.round((p.x-{{ $padL }})/{{ $stepX }});idx=Math.max(0,Math.min(idx,{{ $ptCount-1 }}));let bx={{ $padL }}+idx*{{ $stepX }};let by=p.y;this.show(['{{ implode("','", $labels) }}'][idx],idx,((bx/{{ $w }})*100+'%'),((by/{{ $h }})*100+'%'))"
                        @mouseleave="hide()">
                        <defs>
                            <linearGradient id="gradTotal" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#256abf" stop-opacity="0.20"/>
                                <stop offset="100%" stop-color="#256abf" stop-opacity="0.02"/>
                            </linearGradient>
                            <linearGradient id="gradTerbit" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#059669" stop-opacity="0.20"/>
                                <stop offset="100%" stop-color="#059669" stop-opacity="0.02"/>
                            </linearGradient>
                        </defs>
                        <g x-ref="grafik">
                            {{-- gridlines --}}
                            @for($i=0;$i<=4;$i++)
                                @php $gy = $padT + ($plotH/4)*$i; $gv = $maxVal - ($maxVal/4)*$i; @endphp
                                <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $w-$padR }}" y2="{{ $gy }}" stroke="#e1e0d9" stroke-width="1"/>
                                <text x="{{ $padL - 6 }}" y="{{ $gy + 4 }}" text-anchor="end" font-size="11" fill="#898781" font-family="system-ui,sans-serif">{{ round($gv) }}</text>
                            @endfor
                            {{-- area fills --}}
                            <polygon points="{{ areaPts($totalData, $maxVal, $stepX, $plotH, $padL, $padT, $padB) }}" fill="url(#gradTotal)"/>
                            <polygon points="{{ areaPts($terbitData, $maxVal, $stepX, $plotH, $padL, $padT, $padB) }}" fill="url(#gradTerbit)"/>
                            {{-- lines --}}
                            <polyline points="{{ polyline($totalData, $maxVal, $stepX, $plotH, $padL, $padT) }}" fill="none" stroke="#256abf" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                            <polyline points="{{ polyline($terbitData, $maxVal, $stepX, $plotH, $padL, $padT) }}" fill="none" stroke="#059669" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                            <polyline points="{{ polyline($ditutupData, $maxVal, $stepX, $plotH, $padL, $padT) }}" fill="none" stroke="#dc2626" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke-dasharray="4,3"/>
                            {{-- x-axis labels --}}
                            @foreach($labels as $i => $lbl)
                                <text x="{{ $padL + $i*$stepX }}" y="{{ $h - 8 }}" text-anchor="middle" font-size="11" fill="#898781" font-family="system-ui,sans-serif">{{ $lbl }}</text>
                            @endforeach
                            {{-- end dots + labels --}}
                            @if($ptCount > 0)
                                <circle cx="{{ $padL + ($ptCount-1)*$stepX }}" cy="{{ pt(end($totalData), $maxVal, $plotH, $padT) }}" r="4" fill="#256abf"/>
                                <text x="{{ $padL + ($ptCount-1)*$stepX + 8 }}" y="{{ pt(end($totalData), $maxVal, $plotH, $padT) + 4 }}" font-size="11" font-weight="600" fill="#256abf" font-family="system-ui,sans-serif">{{ end($totalData) }}</text>
                                <circle cx="{{ $padL + ($ptCount-1)*$stepX }}" cy="{{ pt(end($terbitData), $maxVal, $plotH, $padT) }}" r="4" fill="#059669"/>
                                <text x="{{ $padL + ($ptCount-1)*$stepX + 8 }}" y="{{ pt(end($terbitData), $maxVal, $plotH, $padT) + 4 }}" font-size="11" font-weight="600" fill="#059669" font-family="system-ui,sans-serif">{{ end($terbitData) }}</text>
                            @endif
                        </g>
                        {{-- tooltip --}}
                        <template x-if="tooltip !== null">
                            <g>
                                <rect x="0" y="0" width="160" height="78" fill="white" stroke="#e1e0d9" stroke-width="1" rx="6" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.1))"/>
                                <text x="10" y="20" font-size="12" font-weight="600" fill="#0b0b0b" font-family="system-ui,sans-serif" x-text="tooltip"></text>
                                <template x-for="(row, idx) in [['Total','#256abf',{{ json_encode($totalData) }}[tooltip?.[1]]],['Terbit','#059669',{{ json_encode($terbitData) }}[tooltip?.[1]]],['Ditutup','#dc2626',{{ json_encode($ditutupData) }}[tooltip?.[1]]]]" :key="idx">
                                    <g>
                                        <circle cx="18" cy="36+idx*14" r="4" :fill="row[1]"/>
                                        <text x="28" y="40+idx*14" font-size="11" fill="#52514e" font-family="system-ui,sans-serif" x-text="row[0]"></text>
                                        <text x="145" y="40+idx*14" text-anchor="end" font-size="11" font-weight="600" fill="#0b0b0b" font-family="system-ui,sans-serif" x-text="row[2] ?? 0"></text>
                                    </g>
                                </template>
                            </g>
                        </template>
                    </svg>
                </div>
                {{-- Legend --}}
                <div class="flex items-center gap-6 mt-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-6 h-0.5 rounded-full" style="background:#256abf"></span>
                        <span class="text-xs text-slate-600">Total</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-6 h-0.5 rounded-full" style="background:#059669"></span>
                        <span class="text-xs text-slate-600">Terbit</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-6 h-0.5 rounded-full" style="background:#dc2626;border-top:2px dashed #dc2626"></span>
                        <span class="text-xs text-slate-600">Ditutup</span>
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
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500 uppercase">Ditutup</th>
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
                            <td class="px-3 py-2 text-center">
                                @if($s->ditutup > 0)
                                    <span class="inline-flex items-center justify-center min-w-[24px] h-5 rounded-full bg-red-100 px-1.5 text-xs font-semibold text-red-800">{{ $s->ditutup }}</span>
                                @else
                                    <span class="text-slate-300">0</span>
                                @endif
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
