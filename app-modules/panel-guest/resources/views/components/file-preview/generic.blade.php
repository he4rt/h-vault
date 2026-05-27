@props(['file'])

<div class="flex flex-col items-center justify-center py-12 text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-700 mb-4">
        <svg class="w-8 h-8 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
    </div>
    <p class="text-sm text-zinc-500 dark:text-zinc-400">No preview available for this file type.</p>
    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ $file->mime_type }}</p>
</div>
