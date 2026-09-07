<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusPermohonan: string implements HasColor, HasLabel
{
    case Diajukan = 'diajukan';
    case Verifikasi = 'verifikasi';
    case Dikembalikan = 'dikembalikan';
    case ProsesTeknis = 'proses_teknis';
    case Rekomendasi = 'rekomendasi';
    case MenungguRisalah = 'menunggu_risalah';
    case Keputusan = 'keputusan';
    case Diterbitkan = 'diterbitkan';
    case Ditolak = 'ditolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Verifikasi => 'Verifikasi',
            self::Dikembalikan => 'Dikembalikan',
            self::ProsesTeknis => 'Proses Teknis',
            self::Rekomendasi => 'Rekomendasi',
            self::MenungguRisalah => 'Menunggu Risalah',
            self::Keputusan => 'Keputusan',
            self::Diterbitkan => 'IPPT Diterbitkan',
            self::Ditolak => 'Ditolak',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Diajukan => 'gray',
            self::Verifikasi, self::ProsesTeknis => 'info',
            self::Dikembalikan => 'danger',
            self::Rekomendasi, self::MenungguRisalah, self::Keputusan => 'warning',
            self::Diterbitkan => 'success',
            self::Ditolak => 'danger',
        };
    }

    public static function visibleForStaff(): array
    {
        return [self::Diajukan->value, self::Verifikasi->value, self::Dikembalikan->value, self::MenungguRisalah->value, self::Keputusan->value];
    }

    public static function visibleForTimTeknis(): array
    {
        return [
            self::Verifikasi->value,
            self::ProsesTeknis->value,
            self::Rekomendasi->value,
            self::MenungguRisalah->value,
            self::Keputusan->value,
            self::Diterbitkan->value,
            self::Ditolak->value,
        ];
    }

    /**
     * Kabid dapat melihat seluruh Permohonan sebagai konteks review.
     * Hak perubahan tidak mengikuti visibility ini; update tetap ditolak Policy.
     */
    public static function visibleForKabid(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }

    public static function visibleForKadis(): array
    {
        return [self::MenungguRisalah->value, self::Keputusan->value, self::Diterbitkan->value, self::Ditolak->value];
    }
}
