@props(['icon', 'title'])
<div class="mb-5 flex items-center justify-between gap-3 rounded-lg bg-mint px-5 py-3 text-forest">
    <h2 class="flex items-center gap-2 text-[15px] font-semibold">
        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>{{ $title }}
    </h2>
    <span class="text-xs font-medium">{{ $slot }}</span>
</div>
