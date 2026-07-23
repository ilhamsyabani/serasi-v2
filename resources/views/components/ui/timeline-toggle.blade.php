<button type="button"
        @click="expanded = !expanded"
        :aria-expanded="expanded"
        {{ $attributes->merge(['class' => 'ml-1 inline-flex items-center gap-1 text-xs font-medium text-emerald-700 hover:text-emerald-800']) }}>
    <svg class="h-3 w-3 transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
    <span x-text="expanded ? 'Sembunyikan' : 'Timeline'">Timeline</span>
</button>
