<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HasilRisalah: string implements HasColor, HasLabel
{
    case Mendukung = 'mendukung';
    case TidakMendukung = 'tidak_mendukung';
    case DenganCatatan = 'dengan_catatan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mendukung => 'Mendukung',
            self::TidakMendukung => 'Tidak Mendukung',
            self::DenganCatatan => 'Dengan Catatan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Mendukung => 'success',
            self::TidakMendukung => 'danger',
            self::DenganCatatan => 'warning',
        };
    }
}
