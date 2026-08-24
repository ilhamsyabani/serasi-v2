@props(['permohonan'])

@php
    $sla = app(\App\Services\SlaCalculator::class);
    $currentStatus = $permohonan->status_saat_ini;
    $logs = $permohonan->statusLog->groupBy('status');

    // Label + ikon Phosphor per tahap (design_system.md §4).
    $labels = [
        'pengajuan' => ['Pengajuan', 'ph-file-text'],
        'didisposisikan' => ['Disposisi Kepala Balai', 'ph-paper-plane-tilt'],
        'proses_evaluasi' => ['Evaluasi Staff', 'ph-magnifying-glass'],
        'revisi_1' => ['Revisi ke-1', 'ph-pencil-simple'],
        'revisi_2' => ['Revisi ke-2', 'ph-pencil-simple'],
        'revisi_3' => ['Revisi ke-3', 'ph-pencil-simple'],
        'menunggu_surat_pengesahan' => ['Menunggu Surat Pengesahan', 'ph-clock-countdown'],
        'terbit_surat_pengesahan' => ['Terbit Surat Pengesahan', 'ph-seal-check'],
        'ditutup_pengajuan_ulang' => ['Ditutup — Perlu Pengajuan Ulang', 'ph-x-circle'],
    ];

    // Aktor per tahap, supaya "Distribusi Katim" terlihat tanpa perlu status tersendiri
    // (distribusi terjadi saat transisi didisposisikan -> proses_evaluasi).
    $aktor = [
        'didisposisikan' => $permohonan->disposisi?->ketuaTim?->nama
            ? 'Ketua Tim: ' . $permohonan->disposisi->ketuaTim->nama
            : null,
        'proses_evaluasi' => $permohonan->distribusiAktif?->staff?->nama
            ? 'Staff: ' . $permohonan->distribusiAktif->staff->nama
            : null,
    ];

    // Tahap revisi hanya muncul bila siklusnya memang terjadi.
    $steps = ['pengajuan', 'didisposisikan', 'proses_evaluasi'];
    foreach (['revisi_1', 'revisi_2', 'revisi_3'] as $revisi) {
        if ($logs->has($revisi) || $currentStatus === $revisi) {
            $steps[] = $revisi;
        }
    }

    if ($logs->has('ditutup_pengajuan_ulang') || $currentStatus === 'ditutup_pengajuan_ulang') {
        $steps[] = 'ditutup_pengajuan_ulang';
    } else {
        $steps[] = 'menunggu_surat_pengesahan';
        $steps[] = 'terbit_surat_pengesahan';
    }
@endphp

{{-- Perubahan 1: Mengubah space dan margin ol agar border lurus --}}
<ol class="relative border-l border-slate-200 ml-4 space-y-8">
    @foreach($steps as $status)
        @php
            $entries = $logs->get($status);
            $isTutup = $status === 'ditutup_pengajuan_ulang';
            // Status akhir bukan "sedang diproses" — tandai sebagai sudah dilalui.
            $isTerminal = $isTutup || $status === 'terbit_surat_pengesahan';
            $isCurrent = $status === $currentStatus && ! $isTerminal;
            $isDone = ! $isCurrent && $entries !== null;
            $isPending = ! $isCurrent && ! $isDone;

            // `proses_evaluasi` bisa muncul berkali-kali (sekali per siklus revisi):
            // ambil rentang waktu terluas dan jumlahkan durasinya.
            $mulai = $entries?->first()?->waktu_mulai;
            $selesai = $entries?->last()?->waktu_selesai;
            $siklus = $entries?->count() ?? 0;
            $berdurasi = $entries?->whereNotNull('durasi_hari_kerja');
            $durasi = $berdurasi?->isNotEmpty() ? $berdurasi->sum('durasi_hari_kerja') : null;
            $slaTahap = $entries?->last() ? $sla->evaluasiLog($entries->last()) : null;

            [$label, $ikonStatus] = $labels[$status] ?? [\Illuminate\Support\Str::title(str_replace('_', ' ', $status)), 'ph-circle'];

            // Warna & ikon penanda tahap sesuai state.
            if ($isDone && $isTutup)      { $markBg = 'bg-red-500';     $markIcon = 'ph-x'; }
            elseif ($isDone)              { $markBg = 'bg-emerald-500'; $markIcon = $ikonStatus; } // Menggunakan ikon spesifik tahap
            elseif ($isCurrent)           { $markBg = 'bg-amber-500';   $markIcon = $ikonStatus; }
            else                          { $markBg = 'bg-slate-200';   $markIcon = $ikonStatus; }
        @endphp

        {{-- Perubahan 2: Memperbaiki posisi li dan relative left untuk centering bulatannya --}}
        <li class="relative pl-8">
            {{-- Penanda tahap (-left-3 akan memposisikan lingkaran w-6 tepat di tengah border) --}}
            <span class="absolute -left-3 top-0 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white {{ $markBg }} {{ $isCurrent ? 'animate-pulse' : '' }}">
                <i class="ph-bold {{ $markIcon }} text-[12px] {{ $isPending ? 'text-slate-400' : 'text-white' }}" aria-hidden="true"></i>
            </span>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-4">
                <div class="min-w-0 flex-1">
                    {{-- Perubahan 3: Menghapus ikon duplikat di samping teks dan merapikan warna teks --}}
                    <p class="flex items-center gap-1.5 text-sm font-semibold {{ $isDone ? 'text-[#1E3A8A]' : ($isCurrent ? 'text-amber-600' : 'text-slate-400') }}">
                        {{ $label }}
                        @if($siklus > 1)
                            <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">{{ $siklus }}x siklus</span>
                        @endif
                    </p>

                    @if(($isDone || $isCurrent) && ($aktor[$status] ?? null))
                        <p class="mt-1 flex items-center gap-1 text-xs text-slate-500 font-medium">
                            <i class="ph ph-user text-sm" aria-hidden="true"></i>{{ $aktor[$status] }}
                        </p>
                    @endif

                    @if($mulai)
                        <p class="mt-1.5 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-slate-500">
                            <i class="ph ph-clock text-sm text-slate-400" aria-hidden="true"></i>
                            <span>{{ $mulai->format('d M Y, H:i') }}</span>
                            @if($selesai)<span class="text-slate-300">&rarr;</span><span>{{ $selesai->format('d M Y, H:i') }}</span>@endif
                            @if($durasi !== null)<span class="text-slate-300">&middot;</span><span>{{ $durasi }} hari kerja</span>@endif
                            @if($isCurrent)<span class="text-slate-300">&middot;</span><span class="text-amber-600 font-medium">berjalan {{ $mulai->diffForHumans(null, true) }}</span>@endif
                        </p>
                    @else
                        <p class="mt-1 text-xs text-slate-400">Belum dimulai</p>
                    @endif
                </div>

                {{-- Indikator status + SLA --}}
                <div class="flex shrink-0 flex-wrap items-center gap-2 mt-2 sm:mt-0">
                    @if($isCurrent)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                            <i class="ph-fill ph-spinner-gap animate-spin" aria-hidden="true"></i> Sedang Diproses
                        </span>
                    @elseif($isDone && $isTutup)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                            <i class="ph-fill ph-x-circle" aria-hidden="true"></i> Perlu Pengajuan Ulang
                        </span>
                    @elseif($isDone)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            <i class="ph-fill ph-check-circle" aria-hidden="true"></i> Selesai
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-500 ring-1 ring-inset ring-slate-300/60">Belum Dimulai</span>
                    @endif

                    @if($slaTahap && ($isDone || $isCurrent) && $slaTahap['state'] !== \App\Services\SlaCalculator::STATE_TANPA_SLA)
                        <x-ui.sla-badge :sla="$slaTahap" />
                    @endif
                </div>
            </div>
        </li>
    @endforeach
</ol>