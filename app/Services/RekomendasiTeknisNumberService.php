<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RekomendasiTeknis;
use Carbon\CarbonInterface;

class RekomendasiTeknisNumberService
{
    /** Format nomor rekomendasi: REK-TEKNIS/IPPT/YYYY/00001. */
    public function generate(?CarbonInterface $date = null): string
    {
        $date ??= now();
        $prefix = sprintf('REK-TEKNIS/IPPT/%s/', $date->format('Y'));
        $last = RekomendasiTeknis::query()->where('nomor_rekomendasi', 'like', $prefix . '%')->orderByDesc('nomor_rekomendasi')->value('nomor_rekomendasi');
        $sequence = 0;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $m)) $sequence = (int) $m[1];
        return $prefix . str_pad((string) ($sequence + 1), 5, '0', STR_PAD_LEFT);
    }
}
