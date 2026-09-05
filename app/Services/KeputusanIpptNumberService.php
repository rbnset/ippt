<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\KeputusanIppt;
use Carbon\CarbonInterface;

class KeputusanIpptNumberService
{
    /** Format: SK-IPPT/YYYY/00001. */
    public function generate(?CarbonInterface $date = null): string
    {
        $date ??= now();
        $prefix = sprintf('SK-IPPT/%s/', $date->format('Y'));
        $last = KeputusanIppt::query()
            ->where('nomor_keputusan', 'like', $prefix . '%')
            ->orderByDesc('nomor_keputusan')
            ->value('nomor_keputusan');
        $sequence = 0;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $m)) {
            $sequence = (int) $m[1];
        }
        return $prefix . str_pad((string) ($sequence + 1), 5, '0', STR_PAD_LEFT);
    }
}
