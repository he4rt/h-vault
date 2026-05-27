<div>
    @if ($uploadedFile)
        {{-- Upload success state --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm">
            <div class="text-center mb-4">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 mb-3">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold">File uploaded</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $uploadedFile['original_name'] }}</p>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Preview link</label>
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            readonly
                            value="{{ url($uploadedFile['url']) }}"
                            class="flex-1 rounded-md border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-900 px-3 py-2 text-sm font-mono select-all focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            onclick="this.select()"
                        />
                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ url($uploadedFile['url']) }}')"
                            class="shrink-0 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                        >
                            Copy
                        </button>
                    </div>
                </div>

                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Expires {{ $uploadedFile['expires_at'] }}
                </p>
            </div>

            <div class="mt-6 text-center">
                <button
                    wire:click="resetUpload"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline focus:outline-none"
                >
                    Upload another file
                </button>
            </div>
        </div>
    @else
        {{-- Upload form --}}
        <form wire:submit="upload" class="space-y-6">
            <div class="rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 p-8 text-center hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors">
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>

                <label for="file-upload" class="cursor-pointer">
                    <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Choose a file</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400"> or drag and drop</span>
                    <input
                        id="file-upload"
                        type="file"
                        wire:model="file"
                        class="sr-only"
                    />
                </label>

                <p class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">
                    Max {{ config('vault.upload.max_size_mb', 100) }} MB
                </p>

                @if ($file)
                    <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 text-sm text-indigo-700 dark:text-indigo-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ $file->getClientOriginalName() }}
                    </div>
                @endif
            </div>

            @error('file')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            {{-- Options --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 space-y-4">
                <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Options</h3>

                <div>
                    <label for="password" class="block text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Password protection</label>
                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        placeholder="Leave empty for no password"
                        class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-900 px-3 py-2 text-sm placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>

                <div>
                    <label for="expires-in" class="block text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Expires in</label>
                    <select
                        id="expires-in"
                        wire:model="expiresIn"
                        class="w-full rounded-md border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                        <option value="1h">1 hour</option>
                        <option value="6h">6 hours</option>
                        <option value="24h">24 hours</option>
                        <option value="3d">3 days</option>
                        <option value="7d">7 days</option>
                        <option value="1m">1 month</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        id="one-time"
                        type="checkbox"
                        wire:model="isOneTime"
                        class="rounded border-zinc-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label for="one-time" class="text-sm text-zinc-600 dark:text-zinc-400">
                        One-time download (delete after first access)
                    </label>
                </div>
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="upload">Upload</span>
                <span wire:loading wire:target="upload">Uploading...</span>
            </button>
        </form>

        {{-- SDK examples --}}
        <div class="mt-10 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">API Usage</h3>
            <pre class="text-xs font-mono text-zinc-600 dark:text-zinc-400 overflow-x-auto"><code>curl -X PUT {{ url('/api/v1/files') }} \
  -F "file=@photo.png"</code></pre>
        </div>

        {{-- File counter --}}
        <div class="mt-6 text-center">
            <p class="text-sm text-zinc-400 dark:text-zinc-500">
                <span class="font-semibold text-zinc-600 dark:text-zinc-300">{{ number_format($this->fileCount) }}</span>
                files hosted
            </p>
        </div>
    @endif
</div>
