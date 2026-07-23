{{-- Data Table Shadcn-style --}}
@props(['headers' => [], 'actions' => false])
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                @foreach($headers as $header)
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $header }}</th>
                @endforeach
                @if($actions)
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            {{ $slot }}
        </tbody>
    </table>
    @if(empty($slot))
        <div class="px-4 py-12 text-center text-sm text-slate-400">Tidak ada data.</div>
    @endif
</div>
