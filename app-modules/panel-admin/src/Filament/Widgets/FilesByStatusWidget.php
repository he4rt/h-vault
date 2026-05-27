<?php

declare(strict_types=1);

namespace He4rt\Admin\Filament\Widgets;

use BackedEnum;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Files\File;

class FilesByStatusWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $countsByStatus = File::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return collect(FileStatus::cases())
            ->map(function (FileStatus $status) use ($countsByStatus): Stat {
                $count = (int) ($countsByStatus[$status->value] ?? 0);
                $icon = $status->getIcon();
                $iconValue = $icon instanceof BackedEnum ? (string) $icon->value : 'heroicon-o-document';

                return Stat::make($status->getLabel(), (string) $count)
                    ->icon($iconValue);
            })
            ->toArray();
    }
}
