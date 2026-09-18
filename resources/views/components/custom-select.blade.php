@props(['name', 'value' => '', 'options' => [], 'placeholder' => 'Pilih...', 'minWidth' => '160px'])

@php
$safeValue = addslashes($value);
$safePlaceholder = addslashes($placeholder);
$optionsId = 'select-options-' . uniqid();
@endphp

<div class="relative inline-block" style="min-width: {{ $minWidth }};"
    x-data='customSelect({
        open: false,
        selected: @json($value),
        options: @json($options),
        placeholder: @json($placeholder)
    })'
    @click.outside="open = false"
    @keydown.escape.window="if(open) { open = false; $refs.trigger.focus(); }">

    <button type="button" x-ref="trigger"
        @click="open = !open"
        class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-sm font-medium rounded-xl outline-none transition-all cursor-pointer text-left"
        style="background: var(--bg-secondary); border: 1.5px solid var(--border-color); color: var(--text-primary);"
        :style="open ? 'border-color: var(--accent-blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.10);' : ''"
        onmouseenter="if(!this.matches(':focus')) this.style.borderColor='rgba(37,99,235,0.3)'"
        onmouseleave="if(!open) this.style.borderColor='var(--border-color)'">
        <span x-text="selectedLabel" :style="!selected ? 'color: var(--text-muted);' : ''"></span>
        <svg class="w-5 h-5 flex-shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <input type="hidden" name="{{ $name }}" :value="selected" x-ref="hiddenInput">

    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute left-0 mt-1.5 rounded-xl"
        style="min-width: 100%; z-index: 9999; background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 8px 32px rgba(15,23,42,0.18), 0 2px 8px rgba(15,23,42,0.08);">
        <div class="py-1 max-h-60 overflow-y-auto">
            @foreach($options as $val => $label)
            <button type="button"
                @click="selected = '{{ addslashes($val) }}'; open = false; $nextTick(() => $refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true })))"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left transition-all duration-100"
                :class="selected === '{{ addslashes($val) }}' ? 'font-semibold' : ''"
                :style="selected === '{{ addslashes($val) }}'
                    ? 'background: rgba(37,99,235,0.08); color: var(--accent-blue);'
                    : 'color: var(--text-primary);'"
                onmouseenter="this.style.background=this.classList.contains('font-semibold') ? 'rgba(37,99,235,0.12)' : 'rgba(15,23,42,0.05)'"
                onmouseleave="this.style.background=this.classList.contains('font-semibold') ? 'rgba(37,99,235,0.08)' : ''">
                <span x-show="selected === '{{ addslashes($val) }}'" class="flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
                <span x-show="selected !== '{{ addslashes($val) }}'" class="w-4 flex-shrink-0"></span>
                <span>{{ $label }}</span>
            </button>
            @endforeach
        </div>
    </div>
</div>
