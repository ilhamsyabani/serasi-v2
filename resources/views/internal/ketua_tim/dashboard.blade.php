@extends('layouts.internal')

@section('title', 'Dashboard Ketua Tim')
@section('content')
<?php $pageTitle = 'Dashboard'; ?>

{{--
    Dashboard terpadu Ketua Tim (B2): ringkasan beban kerja, status SLA, dan
    daftar permohonan dengan aksi distribusi inline + timeline — dalam satu halaman.
--}}

@if(session('success'))
    <x-ui.alert type="success" class="mb-5">{{ session('success') }}</x-ui.alert>
@endif

{{-- Ringkasan beban --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <x-ui.stat-card :value="$permohonans->count()" label="Permohonan Aktif" description="Di bawah tim Anda" icon="ph-files" />
    <x-ui.stat-card :value="$perluDistribusi->count()" label="Perlu Distribusi" description="Menunggu ditugaskan" icon="ph-paper-plane-tilt" />
    <x-ui.stat-card :value="$permohonans->whereIn('status_saat_ini', ['proses_evaluasi','revisi_1','revisi_2','revisi_3'])->count()" label="Proses Evaluasi" description="Sedang ditinjau staff" icon="ph-magnifying-glass" />
    <x-ui.stat-card :value="$permohonans->where('status_saat_ini', 'menunggu_surat_pengesahan')->count()" label="Menunggu Surat" description="Siap terbit" icon="ph-file-text" />
</div>

{{-- Status SLA & beban kerja per staff, berdampingan --}}
<div class="grid gap-4 lg:grid-cols-3 mb-6">
    <x-ui.card class="lg:col-span-1">
        <x-ui.card-content>
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Status SLA</h3>
            <dl class="space-y-2">
                @foreach([
                    ['on_time', 'Tepat waktu', 'bg-emerald-500', 'text-emerald-700'],
                    ['at_risk', 'Mendekati batas', 'bg-yellow-400', 'text-yellow-700'],
                    ['late', 'Terlambat', 'bg-red-500', 'text-red-700'],
                    ['clock_off', 'Clock-off (menunggu pemohon)', 'bg-amber-400', 'text-amber-700'],
                ] as [$key, $label, $dot, $teks])
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full {{ $dot }}"></span>
                        <dt class="flex-1 text-xs text-slate-600">{{ $label }}</dt>
                        <dd class="text-sm font-semibold {{ $teks }}">{{ $slaRingkasan[$key] }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
                Tahap revisi tidak dihitung sebagai keterlambatan staff (clock-off).
            </p>
        </x-ui.card-content>
    </x-ui.card>

    <x-ui.card class="lg:col-span-2">
        <x-ui.card-content>
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Beban Kerja Staff</h3>
            @if($staffList->isEmpty())
                <p class="text-sm text-slate-400">Belum ada staff sertifikasi aktif.</p>
            @else
                @php $maks = max(1, $bebanKerja->max() ?? 1); @endphp
                <ul class="space-y-3">
                    @foreach($staffList as $s)
                        @php $jumlah = $bebanKerja->get($s->id, 0); @endphp
                        <li class="flex items-center gap-3">
                            <x-ui.avatar :name="$s->nama" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $s->nama }}</p>
                                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-emerald-700" style="width: {{ round($jumlah / $maks * 100) }}%"></div>
                                </div>
                            </div>
                            <span class="shrink-0 text-sm font-semibold text-slate-700">{{ $jumlah }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card-content>
    </x-ui.card>
</div>

{{-- Daftar permohonan --}}
<div class="flex items-center justify-between mb-3">
    <h2 class="text-base font-semibold text-blue-900">Daftar Permohonan Tim</h2>
    <x-ui.button variant="outline" size="sm" href="{{ route('internal.ketua_tim.distribusi.index') }}">
        <i class="ph ph-share-network" aria-hidden="true"></i>
        Kelola Distribusi
        @if($perluDistribusi->count() > 0)
            <span class="ml-1 inline-flex items-center justify-center rounded-full bg-emerald-500 text-white text-[10px] font-semibold h-4 min-w-4 px-1">{{ $perluDistribusi->count() }}</span>
        @endif
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
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Staff</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">SLA</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>

            @forelse($permohonans as $p)
            <x-ui.permohonan-row :permohonan="$p" :colspan="6">
                <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $p->nama_pbf_snapshot }}</td>
                <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                <td class="px-4 py-3 text-slate-600">{{ $p->distribusiAktif?->staff?->nama ?? '—' }}</td>
                <td class="px-4 py-3">
                    <x-ui.sla-badge :sla="app(\App\Services\SlaCalculator::class)->evaluasiPermohonan($p)" />
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    @if($p->status_saat_ini === \App\Models\Permohonan::STATUS_DIDISPOSISIKAN)
                        {{-- Aksi distribusi ada di halaman khusus Distribusi --}}
                        <x-ui.button variant="default" size="sm" href="{{ route('internal.ketua_tim.distribusi.index') }}">Distribusikan</x-ui.button>
                    @endif
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.permohonan.show', $p) }}">Detail</x-ui.button>
                    <x-ui.timeline-toggle />
                </td>
            </x-ui.permohonan-row>
            @empty
            <tbody>
                <tr><td colspan="6"><x-ui.empty-state title="Belum ada permohonan" description="Tidak ada permohonan aktif di bawah tim Anda" /></td></tr>
            </tbody>
            @endforelse
        </table>
        </div>
    </x-ui.card-content>
</x-ui.card>
@endsection
