@extends('layouts.internal')

@section('title', 'Distribusi')

@section('content')
<?php $pageTitle = 'Distribusi ke Staff'; ?>

{{-- Header --}}
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-xl font-bold text-slate-900">Distribusi Permohonan</h2>
        <p class="text-sm text-slate-500 mt-1">Tugaskan permohonan yang telah didisposisikan ke Staff Sertifikasi.</p>
    </div>
</div>

{{-- Ringkasan Beban Kerja Staff --}}
<x-ui.card class="mb-6 shadow-sm border-slate-200">
    <x-ui.card-content>
        <div class="flex items-center gap-2 mb-4">
            <h3 class="text-sm text-slate-800">Beban Kerja Staff Saat Ini</h3>
        </div>

        @if($staffList->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4 text-center">
            <p class="text-sm text-slate-500">Belum ada staff sertifikasi aktif.</p>
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($staffList as $s)
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 transition-colors hover:bg-slate-100/70">
                <div class="flex items-center gap-3 overflow-hidden">
                    <x-ui.avatar :name="$s->nama" size="sm" />
                    <span class="truncate text-sm font-medium text-slate-700">{{ $s->labelEvaluator(auth()->user()) }}</span>
                </div>
                {{-- Badge highlight untuk jumlah tugas --}}
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-700/10">
                    <i class="ph-bold ph-briefcase" aria-hidden="true"></i>
                    {{ $bebanKerja[$s->id] ?? 0 }}
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </x-ui.card-content>
</x-ui.card>

{{-- Filter Form --}}
<form method="GET" action="{{ route('internal.ketua_tim.distribusi.index') }}">
    <div class="flex flex-wrap items-end gap-4 mb-4">
        <div class="flex-1">
            <x-ui.input
                label="Cari Permohonan"
                name="search"
                :value="request('search')"
                placeholder="Ketik NIB atau Nama PBF..." />
        </div>

        <div class="w-38">
            <x-ui.input
                label="Dari Tanggal"
                type="date"
                name="tanggal_dari"
                value="{{ $tanggalDari }}"
                placeholder="DD-MM-YYYY" />
        </div>
        <div class="w-38">
            <x-ui.input
                label="Sampai Tanggal"
                type="date"
                name="tanggal_sampai"
                value="{{ $tanggalSampai }}"
                placeholder="DD-MM-YYYY" />
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="default" type="submit">Terapkan</x-ui.button>
            <x-ui.button variant="outline" type="submit" onclick="this.form.status.value=''; this.form.tanggal_dari.value=''; this.form.tanggal_sampai.value=''; this.form.search.value='';">Reset</x-ui.button>
        </div>
    </div>
</form>


{{-- Data Table --}}
<x-ui.card class="shadow-sm border-slate-200">
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">No. Reg</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">PBF</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($permohonans as $p)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-5 py-4 font-mono text-xs font-semibold text-slate-800 whitespace-nowrap">
                            {{ $p->no_registrasi }}
                        </td>
                        <td class="px-5 py-4 text-slate-700 font-medium">
                            {{ $p->nama_pbf_snapshot }}
                        </td>
                        <td class="px-5 py-4 text-slate-500 text-xs whitespace-nowrap">
                            {{ $p->tanggal_pengajuan?->format('d M Y') }}
                        </td>
                        {{-- x-data wajib: Alpine hanya menjalankan x-on:click di dalam komponen x-data. --}}
                        <td class="px-5 py-4 text-right" x-data>
                            <x-ui.button variant="default" size="sm"
                                x-on:click="$dispatch('buka-distribusi', {{ \Illuminate\Support\Js::from(['uuid' => $p->uuid, 'noReg' => $p->no_registrasi, 'pbf' => $p->nama_pbf_snapshot]) }})">
                                <i class="ph-bold ph-user-plus mr-1.5" aria-hidden="true"></i> Distribusi
                            </x-ui.button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center">
                            <x-ui.empty-state
                                title="Semua Telah Didistribusikan"
                                description="Tidak ada permohonan baru yang perlu ditugaskan ke staff saat ini."
                                />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-2">
                {{ $permohonans->links() }}
            </div>
        </div>
    </x-ui.card-content>
</x-ui.card>

{{-- Modal distribusi: satu untuk semua baris. Dibuka ulang otomatis bila validasi gagal. --}}
<x-ui.modal name="distribusi" title="Tugaskan Evaluator"
    :buka-otomatis="$errors->any() && old('_permohonan')"
    :detail="['uuid' => old('_permohonan'), 'noReg' => old('_no_reg'), 'pbf' => old('_pbf')]">
    <x-slot:subtitle>
        <span class="font-mono" x-text="data.noReg"></span> &middot; <span x-text="data.pbf"></span>
    </x-slot:subtitle>

    <form method="POST" :action="{{ \Illuminate\Support\Js::from(route('internal.ketua_tim.distribusi.store', '__ID__')) }}.replace('__ID__', data.uuid)">
        @csrf
        <input type="hidden" name="_permohonan" :value="data.uuid">
        <input type="hidden" name="_no_reg" :value="data.noReg">
        <input type="hidden" name="_pbf" :value="data.pbf">

        <div class="space-y-4 px-5 py-4">
            <x-ui.select label="Pilih Evaluator" name="staff_id"
                :options="$staffList->mapWithKeys(fn($s) => [$s->id => $s->labelEvaluator(auth()->user()) . ' (' . ($bebanKerja[$s->id] ?? 0) . ' tugas)'])->toArray()"
                placeholder="— Pilih Staff atau diri sendiri —" required
                :error="$errors->first('staff_id')" />
            <p class="-mt-2 text-xs text-slate-500">Angka dalam kurung = jumlah permohonan yang sedang ditangani.</p>
            <x-ui.textarea label="Catatan Khusus (opsional)" name="catatan" :rows="3"
                :value="old('catatan')" placeholder="Masukkan instruksi khusus..."
                :error="$errors->first('catatan')" />
            <p class="text-xs text-slate-500">Staff yang dipilih menerima notifikasi WhatsApp. Pilih diri sendiri bila Anda yang akan mengevaluasi.</p>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3">
            <x-ui.button type="button" variant="ghost" size="sm" x-on:click="buka = false">Batal</x-ui.button>
            <x-ui.button type="submit" size="sm">
                <i class="ph-bold ph-user-plus mr-1.5" aria-hidden="true"></i> Tugaskan
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>
@endsection