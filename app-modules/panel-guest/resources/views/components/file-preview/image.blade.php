@props(['file', 'url'])

<div class="flex items-center justify-center bg-zinc-100 dark:bg-zinc-900 rounded-md overflow-hidden">
    <img
        src="{{ $url }}"
        alt="{{ $file->original_name }}"
        class="max-w-full max-h-[60vh] object-contain"
        loading="lazy"
    />
</div>
