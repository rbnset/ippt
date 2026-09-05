<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HasilRekomendasi: string implements HasColor, HasLabel
{
    case Direkomendasikan = 'direkomendasikan';
    case TidakDirekomendasikan = 'tidak_direkomendasikan';
    case PerluPerbaikan = 'perlu_perbaikan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Direkomendasikan => 'Direkomendasikan',
            self::TidakDirekomendasikan => 'Tidak Direkomendasikan',
            self::PerluPerbaikan => 'Perlu Perbaikan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Direkomendasikan => 'success',
            self::TidakDirekomendasikan => 'danger',
            self::PerluPerbaikan => 'warning',
        };
    }
}
