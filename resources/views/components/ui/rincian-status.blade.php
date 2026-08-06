{{-- Rincian Status Permohonan — reusabel untuk Kabalai, Katim, dan Staff.

     Penggunaan:
       <x-ui.rincian-status
           :permohonans="$permohonans"
           role="kabalai"
           :staff-list="$staffList"
           :katim-list="$katimList" />

     Variabel yang harus dilempar:
     - permohonans: Collection Permohonan (harus sudah di-eager load relasi)
     - role:        'kabalai' | 'katim' | 'staff'
     - staffList:   Collection User (hanya untuk katim/staff)
     - katimList:   Collection User (hanya untuk kabalai)
--}}
@props(['permohonans' => collect(), 'role' => 'kabalai', 'staffList' => collect(), 'katimList' => collect()])

@php
use App\Models\Permohonan;

$rincian = [
    'pengajuan'                 => 'Pengajuan',
    'didisposisikan'            => 'Didisposisikan',
    'proses_evaluasi'           => 'Proses Evaluasi',
    'revisi_1'                  => 'Revisi 1',
    'revisi_2'                  => 'Revisi 2',
    'revisi_3'                  => 'Revisi 3',
    'menunggu_surat_pengesahan' => 'Menunggu Surat',
    'terbit_surat_pengesahan'  => 'Terbit',
    'ditutup_pengajuan_ulang'  => 'Ditutup',
];

// Helper: nama staff / katim dari permohonan
$staffNama = fn($p) => $p->distribusiAktif?->staff?->nama ?? '—';
$katimNama = fn($p) => $p->disposisi?->ketuaTim?->nama ?? '—';
@endphp

@if($role === 'kabalai')
    {{-- Kabalai: pecah per Katim (ketua tim) --}}
    @php
    $byKatim = [];
    foreach ($katimList as $kt) {
        $byKatim[$kt->id] = [
            'nama' => $kt->nama,
            'jumlah' => 0,
            'detail' => [],
        ];
    }
    foreach ($permohonans as $p) {
        $ktId = $p->disposisi?->ketua_tim_id;
        if ($ktId && isset($byKatim[$ktId])) {
            $byKatim[$ktId]['jumlah']++;
            $status = $p->status_saat_ini;
            $byKatim[$ktId]['detail'][$status] = ($byKatim[$ktId]['detail'][$status] ?? 0) + 1;
        }
    }
    @endphp

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase">Nama</th>
                    @foreach($rincian as $kode => $label)
                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase whitespace-nowrap">{{ $label }}</th>
                    @endforeach
                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($katimList as $kt)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-ui.avatar :name="$kt->nama" size="sm" />
                                <span class="text-sm font-medium text-slate-800">{{ $kt->nama }}</span>
                            </div>
                        </td>
                        @foreach($rincian as $kode => $label)
                            <td class="px-3 py-3 text-center">
                                @php $jumlah = $byKatim[$kt->id]['detail'][$kode] ?? 0; @endphp
                                @if($jumlah > 0)
                                    <span class="inline-flex items-center justify-center min-w-[24px] h-6 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold">{{ $jumlah }}</span>
                                @else
                                    <span class="text-slate-300 text-xs">0</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold text-slate-700">{{ $byKatim[$kt->id]['jumlah'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($rincian) + 2 }}" class="px-4 py-6 text-center text-sm text-slate-400">Belum ada Ketua Tim.</td></tr>
                @endforelse
            </tbody>
            @if($katimList->isNotEmpty())
                <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold">
                    <tr>
                        <td class="px-4 py-2.5 text-sm text-slate-700">TOTAL</td>
                        @foreach($rincian as $kode => $label)
                            <td class="px-3 py-2.5 text-center text-sm text-slate-700">
                                @php $total = $permohonans->where('status_saat_ini', $kode)->count(); @endphp
                                {{ $total > 0 ? $total : '—' }}
                            </td>
                        @endforeach
                        <td class="px-4 py-2.5 text-center text-sm text-slate-900">{{ $permohonans->count() }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

@elseif($role === 'katim')
    {{-- Katim: pecah per Staff --}}
    @php
    $byStaff = [];
    foreach ($staffList as $s) {
        $byStaff[$s->id] = [
            'nama' => $s->nama,
            'jumlah' => 0,
            'detail' => [],
        ];
    }
    foreach ($permohonans as $p) {
        $sId = $p->distribusiAktif?->staff_id;
        if ($sId && isset($byStaff[$sId])) {
            $byStaff[$sId]['jumlah']++;
            $status = $p->status_saat_ini;
            $byStaff[$sId]['detail'][$status] = ($byStaff[$sId]['detail'][$status] ?? 0) + 1;
        }
    }
    @endphp

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase">Nama</th>
                    @foreach($rincian as $kode => $label)
                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase whitespace-nowrap">{{ $label }}</th>
                    @endforeach
                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($staffList as $s)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-ui.avatar :name="$s->nama" size="sm" />
                                <span class="text-sm font-medium text-slate-800">{{ $s->nama }}</span>
                            </div>
                        </td>
                        @foreach($rincian as $kode => $label)
                            <td class="px-3 py-3 text-center">
                                @php $jumlah = $byStaff[$s->id]['detail'][$kode] ?? 0; @endphp
                                @if($jumlah > 0)
                                    <span class="inline-flex items-center justify-center min-w-[24px] h-6 rounded-full bg-purple-100 text-purple-800 text-xs font-semibold">{{ $jumlah }}</span>
                                @else
                                    <span class="text-slate-300 text-xs">0</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-semibold text-slate-700">{{ $byStaff[$s->id]['jumlah'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($rincian) + 2 }}" class="px-4 py-6 text-center text-sm text-slate-400">Belum ada Staff Sertifikasi aktif.</td></tr>
                @endforelse
            </tbody>
            @if($staffList->isNotEmpty())
                <tfoot class="bg-slate-50 border-t border-slate-200 font-semibold">
                    <tr>
                        <td class="px-4 py-2.5 text-sm text-slate-700">TOTAL</td>
                        @foreach($rincian as $kode => $label)
                            <td class="px-3 py-2.5 text-center text-sm text-slate-700">
                                @php $total = $permohonans->where('status_saat_ini', $kode)->count(); @endphp
                                {{ $total > 0 ? $total : '—' }}
                            </td>
                        @endforeach
                        <td class="px-4 py-2.5 text-center text-sm text-slate-900">{{ $permohonans->count() }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

@else
    {{-- Staff: tampilkan 9 status dalam grid pill --}}
    @php
    $hitung = $permohonans->countBy('status_saat_ini');
    $total = $permohonans->count();
    @endphp
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    @foreach($rincian as $kode => $label)
                        <th class="px-3 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase whitespace-nowrap">{{ $label }}</th>
                    @endforeach
                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr class="hover:bg-slate-50/50">
                    @foreach($rincian as $kode => $label)
                        <td class="px-3 py-3 text-center">
                            @php $jumlah = $hitung[$kode] ?? 0; @endphp
                            @if($jumlah > 0)
                                <span class="inline-flex items-center justify-center min-w-[24px] h-6 rounded-full bg-cyan-100 text-cyan-800 text-xs font-semibold">{{ $jumlah }}</span>
                            @else
                                <span class="text-slate-300 text-xs">0</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="px-4 py-3 text-center">
                        <span class="text-sm font-bold text-slate-900">{{ $total }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
@endif
