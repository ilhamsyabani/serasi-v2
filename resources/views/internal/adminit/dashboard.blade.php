@extends('layouts.internal')

@section('title', 'Dashboard')
@section('content')
<?php $pageTitle = 'Dashboard Admin IT'; ?>

@php
$counts = $permohonans->countBy('status_saat_ini');
$onProcess = $permohonans->whereNotIn('status_saat_ini', ['terbit_surat_pengesahan', 'ditutup_pengajuan_ulang'])->count();
@endphp

{{-- Statistik Permohonan (paling atas) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-blue-100 uppercase tracking-wide">Total Permohonan</p>
        <p class="text-2xl font-bold mt-1">{{ $stats['totalPermohonan'] }}</p>
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
    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-red-100 uppercase tracking-wide">Notifikasi Gagal</p>
        <p class="text-2xl font-bold mt-1">{{ $stats['notifikasiGagal'] }}</p>
        <p class="text-xs text-red-200 mt-1">butuh perhatian</p>
    </div>
</div>

{{-- Statistik Sistem --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-purple-100 uppercase tracking-wide">User Aktif</p>
        <p class="text-2xl font-bold mt-1">{{ $stats['totalUsers'] }}</p>
        <p class="text-xs text-purple-200 mt-1">akun internal BBPOM</p>
    </div>
    <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-cyan-100 uppercase tracking-wide">PBF Terdaftar</p>
        <p class="text-2xl font-bold mt-1">{{ $stats['totalPbf'] }}</p>
        <p class="text-xs text-cyan-200 mt-1">akun pelaku usaha</p>
    </div>
    <div class="bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl p-4 text-white shadow-sm">
        <p class="text-xs font-medium text-violet-100 uppercase tracking-wide">Ditutup</p>
        <p class="text-2xl font-bold mt-1">{{ $counts['ditutup_pengajuan_ulang'] ?? 0 }}</p>
        <p class="text-xs text-violet-200 mt-1">pengajuan ulang</p>
    </div>
</div>

{{-- Pengaturan OTP Pemohon --}}
<div class="mb-4"
     x-data="{ otpEnabled: {{ $otpPemohonEnabled ? 'true' : 'false' }} }">
    <form action="{{ route('internal.adminit.config-setting.update') }}" method="POST">
        @csrf
        <input type="hidden" name="key" value="otp_pemohon_enabled">
        <input type="hidden" name="value" :value="otpEnabled">
        <x-ui.card>
            <div class="flex items-center justify-between px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                        <i class="ph ph-shield-check text-amber-600 text-xl" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Verifikasi OTP Login Pemohon</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Jika aktif, pemohon wajib verifikasi OTP via WhatsApp saat login pertama kali.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium"
                          :class="otpEnabled ? 'text-emerald-600' : 'text-slate-400'"
                          x-text="otpEnabled ? 'Aktif' : 'Nonaktif'">
                    </span>
                    <button type="submit"
                            @click="otpEnabled = !otpEnabled"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                            :class="otpEnabled ? 'bg-emerald-500' : 'bg-slate-300'"
                            title="Klik untuk toggle">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                              :class="otpEnabled ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
            </div>
        </x-ui.card>
    </form>
</div>

{{-- Keterangan Status --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
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

    {{-- Manajemen Sistem --}}
    <x-ui.card>
        <x-ui.card-header title="Manajemen Sistem" />
        <x-ui.card-content class="space-y-2">
            <x-ui.button variant="default" href="{{ route('internal.adminit.users.index') }}" class="w-full justify-start">
                <i class="ph ph-users-three mr-2" aria-hidden="true"></i>
                Manajemen User & Role →
            </x-ui.button>
            <x-ui.button variant="default" href="{{ route('internal.adminit.hari-libur.index') }}" class="w-full justify-start">
                <i class="ph ph-calendar-x mr-2" aria-hidden="true"></i>
                Hari Libur & Cuti →
            </x-ui.button>
        </x-ui.card-content>
    </x-ui.card>
</div>
@endsection
