{{--
    Baris tabel permohonan yang bisa di-expand untuk menampilkan Timeline Progres Detail.

    Dibungkus <tbody> (bukan <tr>) karena scope Alpine `x-data` harus mencakup baris
    utama DAN baris timeline; dua <tr> bersaudara tidak berbagi scope. HTML mengizinkan
    banyak <tbody> dalam satu <table>.

    Pemakaian:
        <x-ui.permohonan-row :permohonan="$p" :colspan="5">
            <td>...</td>
            <td class="text-right">
                <x-ui.button ... />
                <x-ui.timeline-toggle />
            </td>
        </x-ui.permohonan-row>
--}}
@props(['permohonan', 'colspan' => 5])

<tbody class="divide-y divide-slate-50" x-data="{ expanded: false }">
    <tr class="hover:bg-slate-50 transition-colors">
        {{ $slot }}
    </tr>
    <tr x-show="expanded" x-cloak>
        <td colspan="{{ $colspan }}" class="px-4 py-5 bg-slate-50/70 border-t border-slate-100">
            <x-ui.workflow-timeline :permohonan="$permohonan" />
        </td>
    </tr>
</tbody>
