<?php

declare(strict_types=1);

namespace He4rt\Admin\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Vault\Files\File;
use Illuminate\Support\Number;

class TotalFilesWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalFiles = File::query()->count();

        $totalBytes = (int) File::query()->sum('file_size');

        $uploadedToday = File::query()
            ->whereDate('created_at', today())
            ->count();

        return [
            Stat::make('Total Files', (string) $totalFiles)
                ->icon('heroicon-o-document'),

            Stat::make('Storage Used', Number::fileSize($totalBytes))
                ->icon('heroicon-o-archive-box'),

            Stat::make('Uploaded Today', (string) $uploadedToday)
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}
