<?php

declare(strict_types=1);

namespace He4rt\Vault\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum FileStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Infected = 'infected';
    case Quarantined = 'quarantined';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Clean => 'Clean',
            self::Infected => 'Infected',
            self::Quarantined => 'Quarantined',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Pending => Color::Yellow,
            self::Clean => Color::Green,
            self::Infected => Color::Red,
            self::Quarantined => Color::Orange,
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Clean => Heroicon::CheckCircle,
            self::Infected => Heroicon::ExclamationTriangle,
            self::Quarantined => Heroicon::ShieldExclamation,
        };
    }
}
