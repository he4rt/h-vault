<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\FilamentPanel;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AppPanelProvider extends PanelProvider
{
    private FilamentPanel $panelId = FilamentPanel::App;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->path($this->panelId->value)
            ->id($this->panelId->value)
            ->login()
            ->registration()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->viteTheme(sprintf('resources/css/filament/%s/theme.css', $this->panelId->value))
            ->discoverResources(
                in: modules_path('panel-app/src/Filament/Resources'),
                for: 'He4rt\\PanelApp\\Filament\\Resources',
            )
            ->discoverWidgets(
                in: modules_path('panel-app/src/Filament/Widgets'),
                for: 'He4rt\\PanelApp\\Filament\\Widgets',
            )
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
