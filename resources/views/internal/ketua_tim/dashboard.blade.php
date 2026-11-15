@extends('layouts.internal')

@section('title', 'Dashboard')
@section('content')
<?php $pageTitle = 'Dashboard Ketua Tim'; ?>

@php
$roleBucket = [
    'kabalai' => [],
    'katim'   => [],
    'staff'   => [],
    'pemohon' => [],
];
foreach ($allPermohonans as $p) {
    $s = $p->status_saat_ini;
    if ($s === 'pengajuan') {
        $roleBucket['kabalai'][] = $p;
    } elseif ($s === 'didisposisikan') {
        $roleBucket['katim'][] = $p;
    } elseif (in_array($s, ['proses_evaluasi', 'menunggu_surat_pengesahan'])) {
        $roleBucket['staff'][] = $p;
    } elseif ($s === 'ditutup_pengajuan_ulang') {
        $roleBucket['staff'][] = $p;
    } elseif (in_array($s, \App\Models\Permohonan::STATUS_REVISI)) {
        $roleBucket['pemohon'][] = $p;
    } elseif ($s === 'terbit_surat_pengesahan') {
        $roleBucket['pemohon'][] = $p;
    }
}

$counts = $allPermohonans->countBy('status_saat_ini');
$namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

$statBulanan = collect();
$onProcess = $allPermohonans->whereNotIn('status_saat_ini', ['terbit_surat_pengesahan', 'ditutup_pengajuan_ulang'])->count();
@endphp

{{-- Statistik Permohonan (paling atas) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-blue-100 uppercase tracking-wide">Total</p>
        <p class="text-2xl font-bold mt-1">{{ $allPermohonans->count() }}</p>
        <p class="text-xs text-blue-200 mt-1">seluruh permohonan</p>
    </div>
    <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-emerald-100 uppercase tracking-wide">Terbit</p>
        <p class="text-2xl font-bold mt-1">{{ $counts['terbit_surat_pengesahan'] ?? 0 }}</p>
        <p class="text-xs text-emerald-200 mt-1">surat pengesahan</p>
    </div>
    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-amber-100 uppercase tracking-wide">On Process</p>
        <p class="text-2xl font-bold mt-1">{{ $onProcess }}</p>
        <p class="text-xs text-amber-200 mt-1">sedang diproses</p>
    </div>
</div>

{{-- Status SLA & Beban Kerja per Staff --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <x-ui.card>
        <x-ui.card-header title="Status SLA" />
        <x-ui.card-content>
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
                        <dd class="text-sm font-semibold {{ $teks }}">{{ $slaRingkasan[$key] ?? 0 }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
                Tahap revisi tidak dihitung sebagai keterlambatan staff (clock-off).
            </p>
        </x-ui.card-content>
    </x-ui.card>

    <x-ui.card>
        <x-ui.card-header title="Beban Kerja Staff" />
        <x-ui.card-content>
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
                                    <div class="h-full rounded-full bg-purple-700" style="width: {{ round($jumlah / $maks * 100) }}%"></div>
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

@php $eskalasi = app(\App\Services\EskalasiService::class); @endphp
{{-- Permohonan Tim --}}
<div class="flex items-center justify-between mb-3">
    <h2 class="text-base font-semibold text-blue-900">Daftar Permohonan Tim</h2>
    <x-ui.button variant="default" size="sm" href="{{ route('internal.ketua_tim.distribusi.index') }}">
        <i class="ph ph-share-network" aria-hidden="true"></i>
        Kelola Distribusi
        @if($perluDistribusi->count() > 0)
            <span class="ml-1 inline-flex items-center justify-center rounded-full bg-emerald-500 text-white text-[10px] font-semibold h-4 min-w-4 px-1">{{ $perluDistribusi->count() }}</span>
        @endif
    </x-ui.button>
</div>

<x-ui.permohonan-filter :action="route('internal.ketua_tim.dashboard')" :filter="$filter" />

<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No. Reg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">PBF</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Posisi</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Staff</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">SLA</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                </tr>
            </thead>
            @forelse($permohonans as $p)
            <x-ui.permohonan-row :permohonan="$p" :colspan="7">
                <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900">{{ $p->no_registrasi }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $p->nama_pbf_snapshot }}</td>
                <td class="px-4 py-3">
                    @php
                        $s = $p->status_saat_ini;
                        if ($s === 'pengajuan') {
                            $posisiLabel = 'Kepala Balai';
                            $posisiColor = 'bg-blue-100 text-blue-800';
                        } elseif ($s === 'didisposisikan') {
                            $posisiLabel = 'Ketua Tim';
                            $posisiColor = 'bg-purple-100 text-purple-800';
                        } elseif (in_array($s, ['proses_evaluasi', 'menunggu_surat_pengesahan', 'ditutup_pengajuan_ulang'])) {
                            $posisiLabel = 'Staff';
                            $posisiColor = 'bg-cyan-100 text-cyan-800';
                        } else {
                            $posisiLabel = 'Pemohon';
                            $posisiColor = 'bg-amber-100 text-amber-800';
                        }
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $posisiColor }}">{{ $posisiLabel }}</span>
                </td>
                <td class="px-4 py-3"><x-ui.status-badge :status="$p->status_saat_ini" /></td>
                <td class="px-4 py-3 text-slate-600">{{ $p->distribusiAktif?->staff?->nama ?? '—' }}</td>
                <td class="px-4 py-3">
                    <x-ui.sla-badge :sla="app(\App\Services\SlaCalculator::class)->evaluasiPermohonan($p)" />
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    @if($eskalasi->bisaReminder($p))
                        <button type="button" title="Kirim reminder WhatsApp ke staff"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-amber-600 hover:bg-amber-50"
                                @click="$dispatch('buka-eskalasi', { mode: 'reminder', id: {{ $p->id }}, noReg: @js($p->no_registrasi), staffId: {{ $p->distribusiAktif->staff_id }}, staffNama: @js($p->distribusiAktif->staff?->nama) })">
                            <i class="ph ph-bell-ringing text-base" aria-hidden="true"></i>
                            <span class="sr-only">Kirim reminder</span>
                        </button>
                    @endif
                    @if($eskalasi->bisaReassign($p))
                        <button type="button" title="Alihkan ke staff lain"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-blue-700 hover:bg-blue-50"
                                @click="$dispatch('buka-eskalasi', { mode: 'reassign', id: {{ $p->id }}, noReg: @js($p->no_registrasi), staffId: {{ $p->distribusiAktif->staff_id }}, staffNama: @js($p->distribusiAktif->staff?->nama) })">
                            <i class="ph ph-user-switch text-base" aria-hidden="true"></i>
                            <span class="sr-only">Alihkan staff</span>
                        </button>
                    @endif
                    <x-ui.button variant="ghost" size="sm" href="{{ route('internal.permohonan.show', $p) }}">Detail</x-ui.button>
                    <x-ui.timeline-toggle />
                </td>
            </x-ui.permohonan-row>
            @empty
            <tbody>
                <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400">Belum ada permohonan di bawah tim Anda.</td></tr>
            </tbody>
            @endforelse
        </table>
        </div>
        @if($permohonans->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $permohonans->links() }}
            </div>
        @endif
    </x-ui.card-content>
</x-ui.card>
{{-- M-17: dialog Alihkan Staff / Kirim Reminder (khusus Ketua Tim) --}}
<div x-data="{
        buka: false, mode: 'reassign', id: null, noReg: '', staffId: null, staffNama: '',
        urlReassign: @js(route('internal.ketua_tim.eskalasi.reassign', '__ID__')),
        urlReminder: @js(route('internal.ketua_tim.eskalasi.reminder', '__ID__')),
        get aksi() { return (this.mode === 'reassign' ? this.urlReassign : this.urlReminder).replace('__ID__', this.id); },
     }"
     @buka-eskalasi.window="Object.assign($data, $event.detail); buka = true"
     @keydown.escape.window="buka = false"
     x-show="buka" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
     role="dialog" aria-modal="true">
    <div class="w-full max-w-md rounded-xl bg-white shadow-2xl" @click.outside="buka = false">
        <form method="POST" :action="aksi">
            @csrf
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-base font-semibold text-blue-900" x-text="mode === 'reassign' ? 'Alihkan ke Staff Lain' : 'Kirim Reminder ke Staff'"></h3>
                <p class="mt-0.5 text-xs text-slate-500">
                    <span class="font-mono" x-text="noReg"></span> &middot; saat ini: <span x-text="staffNama || '—'"></span>
                </p>
            </div>

            <div class="space-y-4 px-5 py-4">
                <template x-if="mode === 'reassign'">
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label for="eskalasi_staff" class="text-sm font-medium text-slate-700">Staff tujuan</label>
                            <select id="eskalasi_staff" name="staff_id" required
                                    class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                                <option value="">— Pilih staff —</option>
                                @foreach($staffList as $st)
                                    <option value="{{ $st->id }}" :disabled="staffId == {{ $st->id }}">
                                        {{ $st->nama }} ({{ $bebanKerja[$st->id] ?? 0 }} permohonan)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label for="eskalasi_alasan" class="text-sm font-medium text-slate-700">Alasan pengalihan</label>
                            <textarea id="eskalasi_alasan" name="alasan" rows="3" required minlength="5" maxlength="1000"
                                      placeholder="Contoh: staff sedang cuti, beban kerja tidak seimbang…"
                                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"></textarea>
                        </div>
                        <p class="text-xs text-slate-500">Status dan hitungan SLA tidak berubah. Staff lama dan staff baru menerima notifikasi WhatsApp.</p>
                    </div>
                </template>

                <template x-if="mode === 'reminder'">
                    <div class="space-y-1.5">
                        <label for="eskalasi_catatan" class="text-sm font-medium text-slate-700">Catatan (opsional, hanya untuk arsip)</label>
                        <textarea id="eskalasi_catatan" name="catatan" rows="2" maxlength="500"
                                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"></textarea>
                        <p class="text-xs text-slate-500">Staff akan menerima pesan WhatsApp pengingat untuk permohonan ini.</p>
                    </div>
                </template>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3">
                <x-ui.button type="button" variant="outline" size="sm" @click="buka = false">Batal</x-ui.button>
                <x-ui.button type="submit" variant="default" size="sm">
                    <span x-text="mode === 'reassign' ? 'Alihkan' : 'Kirim Reminder'"></span>
                </x-ui.button>
            </div>
        </form>
    </div>
</div>
@endsection
