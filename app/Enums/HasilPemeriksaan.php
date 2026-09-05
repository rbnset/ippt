<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HasilPemeriksaan: string implements HasColor, HasLabel
{
    case Sesuai = 'sesuai';
    case TidakSesuai = 'tidak_sesuai';
    case PerluPerbaikan = 'perlu_perbaikan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sesuai => 'Sesuai',
            self::TidakSesuai => 'Tidak Sesuai',
            self::PerluPerbaikan => 'Perlu Perbaikan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Sesuai => 'success',
            self::TidakSesuai => 'danger',
            self::PerluPerbaikan => 'warning',
        };
    }
}
