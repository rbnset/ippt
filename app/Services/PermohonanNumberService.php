<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permohonan;
use Carbon\CarbonInterface;

class PermohonanNumberService
{
    /**
     * Format nomor permohonan: IPPT/YYYY/MM/0001.
     * Nomor berjalan kembali dari 0001 setiap bulan.
     */
    public function generate(?CarbonInterface $date = null): string
    {
        $date ??= now();

        $prefix = sprintf('IPPT/%s/%s/', $date->format('Y'), $date->format('m'));

        $lastNumber = Permohonan::query()
            ->where('nomor_permohonan', 'like', $prefix . '%')
            ->orderByDesc('nomor_permohonan')
            ->value('nomor_permohonan');

        /*
         * Do not cast Laravel Stringable directly to int.
         *
         * str(...)->afterLast('/') returns Illuminate\Support\Stringable,
         * while PHP expects a scalar value for (int). Extract the suffix
         * as a normal string first, then validate it before converting.
         */
        $lastSequence = 0;

        if (is_string($lastNumber) && $lastNumber !== '') {
            $lastSlashPosition = strrpos($lastNumber, '/');
            $sequence = $lastSlashPosition === false
                ? ''
                : substr($lastNumber, $lastSlashPosition + 1);

            if ($sequence !== '' && ctype_digit($sequence)) {
                $lastSequence = (int) $sequence;
            }
        }

        return $prefix . str_pad(
            (string) ($lastSequence + 1),
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}
