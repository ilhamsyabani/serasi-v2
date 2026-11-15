{{-- Status Badge — warna berdasarkan 9 status permohonan --}}
@props(['status' => '', 'sla' => null])
@php
$map = [
    'pengajuan'              => ['bg-blue-100 text-blue-800',  'Pengajuan'],
    'didisposisikan'         => ['bg-purple-100 text-purple-800', 'Didisposisikan'],
    'proses_evaluasi'        => ['bg-cyan-100 text-cyan-800',  'Proses Evaluasi'],
    'revisi_1'               => ['bg-amber-100 text-amber-800',  'Revisi ke-1'],
    'revisi_2'               => ['bg-amber-100 text-amber-800',  'Revisi ke-2'],
    'revisi_3'               => ['bg-amber-100 text-amber-800',  'Revisi ke-3'],
    'ditutup_pengajuan_ulang'=> ['bg-red-100 text-red-800',     'Ditutup'],
    'menunggu_surat_pengesahan' => ['bg-violet-100 text-violet-800', 'Menunggu Surat'],
    'terbit_surat_pengesahan'=> ['bg-emerald-100 text-emerald-800', 'Terbit'],
];
$slaMap = [
    'on_time'  => ['text-emerald-600', '●'],
    'at_risk'  => ['text-amber-600',   '●'],
    'late'     => ['text-red-600',     '●'],
];
$pair = $map[$status] ?? ['bg-slate-100 text-slate-800', Str::title(str_replace('_', ' ', $status))];
@endphp
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $pair[0] }}">
    {{ $pair[1] }}
    @if($sla && isset($slaMap[$sla]))
        <span class="{{ $slaMap[$sla][0] }} text-[10px]">{{ $slaMap[$sla][1] }}</span>
    @endif
</span>
