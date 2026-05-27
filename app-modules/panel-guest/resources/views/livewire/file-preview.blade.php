<div>
    @if ($notFound)
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-700 mb-4">
                <svg class="w-8 h-8 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold mb-1">File not found</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">This file does not exist or has been removed.</p>
            <a href="{{ route('guest.landing') }}" class="mt-4 inline-block text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                Go back to upload
            </a>
        </div>
    @elseif ($expired)
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 mb-4">
                <svg class="w-8 h-8 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold mb-1">File expired</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">This file has expired and is no longer available.</p>
            <a href="{{ route('guest.landing') }}" class="mt-4 inline-block text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                Upload a new file
            </a>
        </div>
    @elseif (! $unlocked)
        {{-- Password prompt --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-700 mb-4">
                    <svg class="w-8 h-8 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold">Password required</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">This file is password protected.</p>
            </div>

            <form wire:submit="unlock" class="space-y-4">
                <div>
                    <input
                        type="password"
                        wire:model="password"
                        placeholder="Enter password"
                        class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-900 px-3 py-2 text-sm placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        autofocus
                    />
                    @if ($error)
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
                    @endif
                </div>
                <button
                    type="submit"
                    class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 transition-colors"
                >
                    Unlock
                </button>
            </form>
        </div>
    @else
        {{-- File preview --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden shadow-sm">
            {{-- File info header --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold truncate">{{ $file->original_name }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            {{ $this->formattedSize }} &middot; {{ $file->mime_type }}
                            @if ($file->expires_at)
                                &middot; Expires {{ $file->expires_at->diffForHumans() }}
                            @endif
                        </p>
                    </div>
                    <a
                        href="{{ $this->downloadUrl }}"
                        class="shrink-0 inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </a>
                </div>
            </div>

            {{-- Preview area --}}
            <div class="p-4">
                @if ($file->isProtected())
                    {{-- No inline preview for password-protected files --}}
                    <x-panel-guest::file-preview.generic :file="$file" />
                @else
                    @switch($this->previewType)
                        @case('image')
                            <x-panel-guest::file-preview.image :file="$file" :url="$this->downloadUrl" />
                            @break
                        @case('video')
                            <x-panel-guest::file-preview.video :file="$file" :url="$this->downloadUrl" />
                            @break
                        @case('audio')
                            <x-panel-guest::file-preview.audio :file="$file" :url="$this->downloadUrl" />
                            @break
                        @case('pdf')
                            <x-panel-guest::file-preview.pdf :file="$file" :url="$this->downloadUrl" />
                            @break
                        @default
                            <x-panel-guest::file-preview.generic :file="$file" />
                    @endswitch
                @endif
            </div>
        </div>
    @endif
</div>
