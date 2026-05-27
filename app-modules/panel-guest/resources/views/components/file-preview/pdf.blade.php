@props(['file', 'url'])

<div class="rounded-md overflow-hidden bg-zinc-100 dark:bg-zinc-900">
    <iframe
        src="{{ $url }}"
        class="w-full h-[70vh] border-0"
        title="{{ $file->original_name }}"
    ></iframe>
</div>
