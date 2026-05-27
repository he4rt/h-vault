<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Vault\Files\File;
use Illuminate\Support\Number;

class StorageUsageWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id();

        $totalBytes = File::query()
            ->where('uploaded_by', $userId)
            ->sum('file_size');

        $fileCount = File::query()
            ->where('uploaded_by', $userId)
            ->count();

        $activeFileCount = File::query()
            ->where('uploaded_by', $userId)
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->count();

        return [
            Stat::make('Total Storage Used', Number::fileSize((int) $totalBytes))
                ->icon('heroicon-o-archive-box'),

            Stat::make('Total Files', (string) $fileCount)
                ->icon('heroicon-o-document'),

            Stat::make('Active Files', (string) $activeFileCount)
                ->icon('heroicon-o-check-circle'),
        ];
    }
}
