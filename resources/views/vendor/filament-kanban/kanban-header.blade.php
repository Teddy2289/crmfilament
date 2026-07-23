<div class="flex items-center justify-between gap-2 mb-2 px-3">
    <div class="flex items-center gap-2 min-w-0">
        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 pk-dot-{{ $colorKey }}"></span>
        <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-200 truncate">
            {{ $status['title'] }}
        </h3>
    </div>
    <span class="flex-shrink-0 inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded-full text-xs font-bold pk-badge-{{ $colorKey }}">
        {{ count($status['records']) }}
    </span>
</div>
