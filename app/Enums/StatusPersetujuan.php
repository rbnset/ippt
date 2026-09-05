<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Dipakai bersama oleh RekomendasiTeknis dan KeputusanIppt,
 * karena keduanya memiliki alur persetujuan yang identik:
 * draf -> diajukan -> disetujui | ditolak
 */
enum StatusPersetujuan: string implements HasColor, HasLabel
{
    case Draf = 'draf';
    case Diajukan = 'diajukan';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Diajukan => 'Diajukan Review',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Diajukan => 'warning',
            self::Disetujui => 'success',
            self::Ditolak => 'danger',
        };
    }

    /**
     * Status yang masih boleh diedit/dihapus oleh penyusun.
     */
    public static function editableValues(): array
    {
        return [self::Draf->value, self::Ditolak->value];
    }
}
