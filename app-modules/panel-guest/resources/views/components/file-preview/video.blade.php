@props(['file', 'url'])

<div class="flex items-center justify-center bg-zinc-900 rounded-md overflow-hidden">
    <video
        controls
        preload="metadata"
        class="max-w-full max-h-[60vh]"
    >
        <source src="{{ $url }}" type="{{ $file->mime_type }}">
        Your browser does not support the video element.
    </video>
</div>
