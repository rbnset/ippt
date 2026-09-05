<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PemeriksaanLapangan;
use Carbon\CarbonInterface;

class PemeriksaanLapanganNumberService
{
    /** Format nomor BAP: BAP/IPPT/YYYY/00001. */
    public function generate(?CarbonInterface $date = null): string
    {
        $date ??= now();
        $prefix = sprintf('BAP/IPPT/%s/', $date->format('Y'));
        $last = PemeriksaanLapangan::query()->where('nomor_bap', 'like', $prefix . '%')->orderByDesc('nomor_bap')->value('nomor_bap');
        $sequence = 0;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $m)) $sequence = (int) $m[1];
        return $prefix . str_pad((string) ($sequence + 1), 5, '0', STR_PAD_LEFT);
    }
}
