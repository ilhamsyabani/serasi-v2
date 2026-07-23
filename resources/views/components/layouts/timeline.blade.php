{{--
    Timeline kronologis riwayat status permohonan (dipakai di halaman detail).
    Gaya mengikuti design_system.md: Phosphor Icons, palet emerald/amber/navy/slate.
--}}
@props(['logs' => collect()])

@php
    // Ikon Phosphor per status untuk penanda tahap.
    $ikonStatus = [
        'pengajuan' => 'ph-file-text',
        'didisposisikan' => 'ph-paper-plane-tilt',
        'proses_evaluasi' => 'ph-magnifying-glass',
        'revisi_1' => 'ph-pencil-simple',
        'revisi_2' => 'ph-pencil-simple',
        'revisi_3' => 'ph-pencil-simple',
        'menunggu_surat_pengesahan' => 'ph-clock-countdown',
        'terbit_surat_pengesahan' => 'ph-seal-check',
        'ditutup_pengajuan_ulang' => 'ph-x-circle',
    ];
@endphp

<ol class="relative border-l border-slate-200 ml-3 space-y-6">
    @forelse($logs as $log)
        @php
            $berjalan = $log->waktu_selesai === null;
            $ikon = $ikonStatus[$log->status] ?? 'ph-circle';
            // Clock-off = amber (menunggu pemohon), status akhir tutup = merah, lainnya emerald.
            if ($log->status === 'ditutup_pengajuan_ulang') { $bg = 'bg-red-500'; }
            elseif ($log->is_clock_off) { $bg = 'bg-amber-400'; }
            else { $bg = 'bg-emerald-500'; }
        @endphp
        <li class="ml-6 relative">
            <span class="absolute -left-[22px] flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white {{ $bg }} {{ $berjalan ? 'animate-pulse' : '' }}">
                <i class="ph-bold {{ $ikon }} text-[13px] text-white" aria-hidden="true"></i>
            </span>
            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4">
                <div>
                    <p class="text-sm font-semibold text-blue-900">
                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $log->status)) }}
                    </p>
                    @if($log->durasi_hari_kerja !== null)
                        <p class="text-xs text-slate-400">{{ $log->durasi_hari_kerja }} hari kerja</p>
                    @endif
                </div>
                <div class="sm:ml-auto text-right">
                    <p class="flex items-center justify-end gap-1 text-xs text-slate-500">
                        <i class="ph ph-clock text-sm" aria-hidden="true"></i>{{ $log->waktu_mulai?->format('d M Y, H:i') }}
                    </p>
                    @if($log->waktu_selesai)
                        <p class="text-xs text-slate-400">&rarr; {{ $log->waktu_selesai->format('d M Y, H:i') }}</p>
                    @endif
                    @if($log->is_clock_off)
                        <span class="inline-flex items-center gap-1 text-xs text-amber-600 font-medium mt-0.5">
                            <i class="ph-fill ph-pause-circle text-sm" aria-hidden="true"></i> Clock-off
                        </span>
                    @endif
                </div>
            </div>
        </li>
    @empty
        <li class="text-sm text-slate-400 ml-6">Belum ada aktivitas.</li>
    @endforelse
</ol>
