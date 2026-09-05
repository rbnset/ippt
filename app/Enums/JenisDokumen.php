<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisDokumen: string implements HasLabel
{
    case Ktp = 'ktp';
    case KtpPemegangKuasa = 'ktp_pemegang_kuasa';
    case BuktiHak = 'bukti_hak';
    case SuratKuasa = 'surat_kuasa';
    case SuratTidakSengketa = 'surat_tidak_sengketa';
    case Pbb = 'pbb';
    case SitePlan = 'site_plan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ktp => 'KTP Pemohon',
            self::KtpPemegangKuasa => 'KTP Pemegang Kuasa',
            self::BuktiHak => 'Bukti Hak Atas Tanah',
            self::SuratKuasa => 'Surat Kuasa',
            self::SuratTidakSengketa => 'Surat Pernyataan Tidak Sengketa',
            self::Pbb => 'Bukti Pembayaran PBB Terbaru',
            self::SitePlan => 'Site Plan / Denah dan Koordinat',
        };
    }
}
