@props(['file', 'url'])

<div class="flex items-center justify-center p-6 bg-zinc-100 dark:bg-zinc-900 rounded-md">
    <audio controls preload="metadata" class="w-full max-w-md">
        <source src="{{ $url }}" type="{{ $file->mime_type }}">
        Your browser does not support the audio element.
    </audio>
</div>
