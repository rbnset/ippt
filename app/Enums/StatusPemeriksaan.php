<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusPemeriksaan: string implements HasColor, HasLabel
{
    case Draf = 'draf';
    case Final = 'final';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Final => 'Final',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draf => 'warning',
            self::Final => 'success',
        };
    }
}
