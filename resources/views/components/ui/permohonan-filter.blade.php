@props(['action', 'filter' => []])

@php $adaFilter = collect($filter)->filter()->isNotEmpty(); @endphp

<form method="GET" action="{{ $action }}" class="mb-4">
    <div class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px]">
            <x-ui.input label="Search" name="search" :value="$filter['search'] ?? ''" placeholder="Ketik NIB atau Nama PBF..." />
        </div>
        <div class="w-64">
            <x-ui.select
                label="Status"
                name="status"
                :options="['' => 'Semua Status'] + \App\Models\Permohonan::statusList()"
                :selected="$filter['status'] ?? ''"
            />
        </div>
        <div class="w-38">
            <x-ui.input label="Dari Tanggal" type="date" name="tanggal_dari" value="{{ $filter['tanggal_dari'] ?? '' }}" />
        </div>
        <div class="w-38">
            <x-ui.input label="Sampai Tanggal" type="date" name="tanggal_sampai" value="{{ $filter['tanggal_sampai'] ?? '' }}" />
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="default" type="submit">Terapkan</x-ui.button>
            @if($adaFilter)
                <x-ui.button variant="outline" href="{{ $action }}">Reset</x-ui.button>
            @endif
        </div>
    </div>
</form>
