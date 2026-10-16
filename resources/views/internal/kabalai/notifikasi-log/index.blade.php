@extends('layouts.internal')

@section('title', 'Log Notifikasi')
@section('content')
<?php
$pageTitle = 'Log Notifikasi';
use Illuminate\Support\Str;
?>

<div class="flex items-start justify-between mb-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Log Riwayat Notifikasi</h2>
        <p class="text-sm text-slate-500">Riwayat pengiriman Email & WhatsApp untuk semua permohonan</p>
    </div>
    @if($stats['gagal'] > 0)
        <form action="{{ route('internal.adminit.notifikasi-log.resend-all') }}" method="POST"
              onsubmit="return confirmResend(event, this, true);">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-semibold bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 shadow-sm transition-colors cursor-pointer">
                <i class="ph ph-arrows-clockwise text-base" aria-hidden="true"></i>
                Kirim Ulang Semua ({{ $stats['gagal'] }})
            </button>
        </form>
    @endif
</div>

{{-- Statistik Ringkasan --}}
<div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-6">
    <x-ui.stat-card label="Total" :value="$stats['total']" icon="ph-bell" color="slate" />
    <x-ui.stat-card label="Email" :value="$stats['email']" icon="ph-envelope" color="blue" />
    <x-ui.stat-card label="WhatsApp" :value="$stats['whatsapp']" icon="ph-whatsapp-logo" color="emerald" />
    <x-ui.stat-card label="Terkirim" :value="$stats['terkirim']" icon="ph-check-circle" color="emerald" />
    <x-ui.stat-card label="Gagal" :value="$stats['gagal']" icon="ph-x-circle" color="red" />
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('internal.adminit.notifikasi-log.index') }}" class="mb-4">
    <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[140px]">
            <x-ui.input label="Search" name="search" :value="request('search')" placeholder="Nama PBF atau No. Reg..." />
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
            <select name="status" class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none w-32">
                <option value="">Semua</option>
                <option value="terkirim" {{ request('status') === 'terkirim' ? 'selected' : '' }}>Terkirim</option>
                <option value="gagal" {{ request('status') === 'gagal' ? 'selected' : '' }}>Gagal</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Channel</label>
            <select name="channel" class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none w-32">
                <option value="">Semua</option>
                <option value="email" {{ request('channel') === 'email' ? 'selected' : '' }}>Email</option>
                <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Permohonan</label>
            <select name="permohonan_id" class="border border-slate-200 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none w-48">
                <option value="">Semua</option>
                @foreach($permohonanList as $p)
                    <option value="{{ $p->id }}" {{ request('permohonan_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->no_registrasi }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 items-end">
            <x-ui.button variant="default" type="submit">Terapkan</x-ui.button>
            <a href="{{ route('internal.adminit.notifikasi-log.index') }}">
                <x-ui.button variant="outline" type="button">Reset</x-ui.button>
            </a>
        </div>
    </div>
</form>

<x-ui.card>
    <x-ui.card-content class="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tanggal & Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Permohonan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Channel</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Penerima</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Jenis Notifikasi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($logs as $log)
                    @php
                        $tujuan = $log->tujuan();
                        $isEmail = $log->channel === 'email';
                        $isGagal = $log->status_kirim === 'gagal';
                        $isTerkirim = $log->status_kirim === 'terkirim';
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        {{-- Tanggal --}}
                        <td class="px-4 py-3 text-slate-600 text-xs whitespace-nowrap">
                            <span class="block font-medium">{{ $log->created_at->format('d M Y') }}</span>
                            <span class="text-slate-400">{{ $log->created_at->format('H:i:s') }}</span>
                        </td>

                        {{-- Permohonan --}}
                        <td class="px-4 py-3">
                            @if($log->permohonan)
                                <a href="{{ route('internal.permohonan.show', $log->permohonan) }}"
                                   class="font-mono text-xs text-blue-600 hover:underline" title="{{ $log->permohonan->nama_pbf_snapshot }}">
                                    {{ $log->permohonan->no_registrasi }}
                                </a>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>

                        {{-- Channel --}}
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                'bg-blue-100 text-blue-700' => $isEmail,
                                'bg-emerald-100 text-emerald-700' => !$isEmail,
                            ])>
                                @if($isEmail)
                                    <i class="ph ph-envelope" aria-hidden="true"></i>Email
                                @else
                                    <i class="ph ph-whatsapp-logo" aria-hidden="true"></i>WhatsApp
                                @endif
                            </span>
                        </td>

                        {{-- Penerima --}}
                        <td class="px-4 py-3">
                            @if($tujuan)
                                @if($log->tujuan_tipe === 'pemohon')
                                    <div class="text-xs">
                                        <span class="font-medium text-slate-700">{{ $tujuan->nama_pbf ?? $log->permohonan?->nama_pbf_snapshot ?? '-' }}</span>
                                        @if($isEmail)
                                            <span class="block text-slate-400">{{ $tujuan->email ?? $log->permohonan?->email_snapshot ?? $log->permohonan?->pbf?->email ?? '-' }}</span>
                                        @else
                                            <span class="block text-slate-400">{{ $tujuan->no_whatsapp ?? $log->permohonan?->no_wa_snapshot ?? $log->permohonan?->pbf?->no_whatsapp ?? '-' }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-xs">
                                        <span class="font-medium text-slate-700">{{ $tujuan->nama }} <span class="text-slate-400 font-normal">({{ ucfirst(str_replace('_', ' ', $log->tujuan_tipe)) }})</span></span>
                                        @if($isEmail)
                                            <span class="block text-slate-400">{{ $tujuan->email ?? '-' }}</span>
                                        @else
                                            <span class="block text-slate-400">{{ $tujuan->no_whatsapp ?? '-' }}</span>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>

                        {{-- Jenis --}}
                        <td class="px-4 py-3">
                            <span class="font-medium text-xs text-slate-700">{{ $log->label }}</span>
                            <span class="block font-mono text-xs text-slate-400">{{ $log->template_kode ?? '-' }}</span>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3">
                            @if($isTerkirim)
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>Terkirim
                                </span>
                                @if($log->sent_at)
                                    <span class="block text-xs text-slate-400 mt-0.5">{{ $log->sent_at->format('H:i:s') }}</span>
                                @endif
                                @if($log->retry_count > 0)
                                    <span class="block text-[11px] text-slate-400 mt-0.5">Retry: {{ $log->retry_count }}x</span>
                                @endif
                            @elseif($log->status_kirim === 'pending')
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600">
                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>Pending
                                </span>
                            @else
                                <div>
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600">
                                        <span class="h-2 w-2 rounded-full bg-red-500"></span>Gagal
                                    </span>
                                    @if($log->retry_count > 0)
                                        <span class="block text-[11px] text-slate-400 mt-0.5">Retry: {{ $log->retry_count }}x</span>
                                    @endif
                                    @if($log->error_message)
                                        <span class="block text-xs text-red-400 mt-0.5 max-w-[200px] truncate"
                                              title="{{ $log->error_message }}">
                                            {{ Str::limit($log->error_message, 40) }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($isGagal)
                                <form action="{{ route('internal.adminit.notifikasi-log.resend', $log) }}" method="POST" class="inline"
                                      onsubmit="return confirmResend(event, this, false, '{{ $log->channel }}');">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 transition-colors border border-red-200 shadow-sm cursor-pointer">
                                        <i class="ph ph-arrows-clockwise" aria-hidden="true"></i>Kirim Ulang
                                    </button>
                                </form>
                            @else
                                <div class="inline-flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                        <i class="ph-fill ph-check-circle" aria-hidden="true"></i>Terkirim
                                    </span>
                                    <form action="{{ route('internal.adminit.notifikasi-log.resend', $log) }}" method="POST" class="inline"
                                          onsubmit="return confirmResend(event, this, false, '{{ $log->channel }}', true);">
                                        @csrf
                                        <button type="submit" title="Kirim ulang kembali"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs text-slate-500 hover:text-slate-800 hover:bg-slate-100 border border-slate-200 transition-colors cursor-pointer">
                                            <i class="ph ph-arrows-clockwise" aria-hidden="true"></i>Kirim Ulang
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <i class="ph ph-bell-slash text-4xl text-slate-300 mb-3" aria-hidden="true"></i>
                                <p class="text-sm font-medium text-slate-500">Belum ada log notifikasi</p>
                                <p class="text-xs text-slate-400 mt-1">Log akan muncul setelah ada permohonan yang dibuat.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card-content>
</x-ui.card>

@if($logs->hasPages())
<div class="mt-4">
    {{ $logs->links() }}
</div>
@endif

<script>
function confirmResend(event, form, isAll = false, channel = '', isResendAgain = false) {
    if (event) {
        event.preventDefault();
    }

    if (!window.Swal) {
        const fallbackMsg = isAll
            ? 'Kirim ulang semua notifikasi yang gagal?'
            : (isResendAgain ? 'Kirim ulang notifikasi ini lagi?' : 'Kirim ulang notifikasi ini?');
        if (confirm(fallbackMsg)) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;
            form.submit();
        }
        return false;
    }

    let title = 'Kirim Ulang Notifikasi?';
    let text = 'Notifikasi akan dikirim ulang ke alamat/nomor tujuan.';
    let icon = 'question';
    let confirmBtnText = 'Ya, Kirim Ulang';
    let confirmBtnColor = '#059669';

    if (isAll) {
        title = 'Kirim Ulang Semua?';
        text = 'Semua notifikasi yang berstatus gagal akan dikirim ulang secara otomatis.';
        icon = 'warning';
        confirmBtnText = 'Ya, Kirim Semua';
        confirmBtnColor = '#dc2626';
    } else if (channel === 'email') {
        title = isResendAgain ? 'Kirim Ulang Email Ini Lagi?' : 'Kirim Ulang Email?';
        text = 'Pesan email akan dikirim ulang ke alamat email penerima.';
        confirmBtnColor = '#2563eb';
    } else if (channel === 'whatsapp') {
        title = isResendAgain ? 'Kirim Ulang WhatsApp Ini Lagi?' : 'Kirim Ulang WhatsApp?';
        text = 'Pesan WhatsApp akan dikirim ulang ke nomor WhatsApp penerima.';
        confirmBtnColor = '#059669';
    }

    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: confirmBtnColor,
        cancelButtonColor: '#64748b',
        confirmButtonText: confirmBtnText,
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: false,
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Sedang Mengirim...',
                text: 'Mohon tunggu, proses pengiriman sedang berlangsung.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                const iconElem = btn.querySelector('i');
                if (iconElem) {
                    iconElem.classList.add('animate-spin');
                }
            }

            form.submit();
        }
    });

    return false;
}
</script>
@endsection
