<?php

declare(strict_types=1);

use He4rt\PanelGuest\Livewire\FilePreview;
use He4rt\PanelGuest\Livewire\Landing;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/', Landing::class)->name('guest.landing');
    Route::get('/preview/{token}/{filename}', FilePreview::class)
        ->where('filename', '.*')
        ->name('guest.file-preview');
});
