<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JenisKeputusan: string implements HasColor, HasLabel
{
    case Terbit = 'terbit';
    case Tolak = 'tolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::Terbit => 'IPPT Diterbitkan',
            self::Tolak => 'Permohonan Ditolak',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Terbit => 'success',
            self::Tolak => 'danger',
        };
    }
}
