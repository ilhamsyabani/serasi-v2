{{--
    Timeline kronologis riwayat status permohonan (dipakai di halaman detail).
    Gaya mengikuti design_system.md: Phosphor Icons, palet emerald/amber/navy/slate.
--}}
@props(['logs' => collect()])

@php
    // Ikon Phosphor per status untuk penanda tahap.
    $ikonStatus = [
        'pengajuan'                 => 'ph-file-text',
        'didisposisikan'            => 'ph-paper-plane-tilt',
        'proses_evaluasi'           => 'ph-magnifying-glass',
        'revisi_1'                  => 'ph-pencil-simple',
        'revisi_2'                  => 'ph-pencil-simple',
        'revisi_3'                  => 'ph-pencil-simple',
        'menunggu_surat_pengesahan' => 'ph-clock-countdown',
        'terbit_surat_pengesahan'   => 'ph-seal-check',
        'ditutup_pengajuan_ulang'   => 'ph-x-circle',
    ];
@endphp

{{-- Gunakan border-l dan ml-3 agar ada ruang untuk letak setengah ikon di sebelah kiri garis --}}
<ol class="relative border-l border-slate-200 ml-3 space-y-6">
    @forelse($logs as $log)
        @php
            $berjalan = $log->waktu_selesai === null;
            $ikon     = $ikonStatus[$log->status] ?? 'ph-circle';
            
            if ($log->status === 'ditutup_pengajuan_ulang') { 
                $bg = 'bg-red-500'; 
            } elseif ($log->is_clock_off) { 
                $bg = 'bg-amber-400'; 
            } else { 
                $bg = 'bg-emerald-500'; 
            }
        @endphp
        
        {{-- KUNCI PERBAIKAN: Gunakan pl-8 (padding), BUKAN ms-6 (margin) --}}
        <li class="relative pl-8 group">
            
            {{-- Indikator Ikon Bulat --}}
            {{-- -left-3 akan menarik ikon 24px (w-6) tepat 12px ke kiri sehingga bagian tengahnya persis berada di atas garis border-l --}}
            <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white {{ $bg }} {{ $berjalan ? 'animate-pulse shadow-sm' : '' }}">
                <i class="ph-bold {{ $ikon }} text-[12px] text-white" aria-hidden="true"></i>
            </span>
            
            {{-- Konten Timeline --}}
            <div class="flex flex-col gap-2 pt-0.5">
                {{-- Judul Status & Durasi --}}
                <div>
                    <p class="text-sm font-bold text-slate-800 leading-tight">
                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $log->status)) }}
                    </p>
                    @if($log->durasi_hari_kerja !== null)
                        <p class="text-[11px] font-medium text-slate-500 mt-0.5">
                            Menghabiskan {{ $log->durasi_hari_kerja }} hari kerja
                        </p>
                    @endif
                </div>
                
                {{-- Detail Waktu --}}
                <div class="bg-slate-50 rounded-lg p-2.5 border border-slate-100 w-full group-hover:bg-slate-100/70 transition-colors">
                    <div class="flex items-start gap-2 text-xs text-slate-600">
                        <i class="ph-fill ph-play-circle text-slate-400 mt-0.5 text-[13px]"></i>
                        <span class="font-medium">{{ $log->waktu_mulai?->format('d M Y, H:i') }}</span>
                    </div>
                    
                    @if($log->waktu_selesai)
                        <div class="flex items-start gap-2 text-xs text-slate-500 mt-1.5">
                            <i class="ph-fill ph-check-circle text-slate-300 mt-0.5 text-[13px]"></i>
                            <span>Selesai pada {{ $log->waktu_selesai->format('d M Y, H:i') }}</span>
                        </div>
                    @endif
                    
                    @if($log->is_clock_off)
                        <div class="inline-flex items-center gap-1.5 text-[11px] text-amber-700 font-semibold bg-amber-100/60 px-2 py-1 rounded mt-2.5 border border-amber-200/50">
                            <i class="ph-fill ph-pause-circle text-amber-500"></i> Clock-off (Menunggu Pemohon)
                        </div>
                    @endif
                </div>
            </div>
        </li>
    @empty
        {{-- State Kosong --}}
        <li class="relative pl-8">
            <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white bg-slate-200">
                <i class="ph-bold ph-dots-three text-[12px] text-slate-400" aria-hidden="true"></i>
            </span>
            <div class="py-2 flex flex-col items-start justify-center border border-dashed border-slate-200 rounded-lg bg-slate-50 p-4 text-center">
                <p class="text-sm text-slate-500 font-medium">Belum ada aktivitas</p>
                <p class="text-xs text-slate-400 mt-0.5">Riwayat permohonan akan muncul di sini.</p>
            </div>
        </li>
    @endforelse
</ol>