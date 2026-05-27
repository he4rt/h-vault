<?php

declare(strict_types=1);

namespace He4rt\PanelGuest;

use He4rt\PanelGuest\Livewire\FilePreview;
use He4rt\PanelGuest\Livewire\Landing;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PanelGuestServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'panel-guest');
        $this->loadRoutesFrom(__DIR__.'/../resources/routes/web.php');

        Livewire::component('landing', Landing::class);
        Livewire::component('file-preview', FilePreview::class);
    }
}
