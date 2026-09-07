<?php

namespace App\Services;

use App\Enums\StatusPemeriksaan;
use App\Models\RekomendasiTeknis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class RekomendasiTeknisPdfService
{
    public function generate(RekomendasiTeknis $rekomendasi)
    {
        $rekomendasi->load([
            'permohonan.pemohon',
            'disusunOleh',
            'direviewOleh',
            'disetujuiOleh',
        ]);

        $bap = $rekomendasi->permohonan?->pemeriksaanLapangan()
            ->where('status', StatusPemeriksaan::Final->value)
            ->orderByDesc('versi')
            ->first();

        $bap?->load('ketuaTim');

        return Pdf::loadView('pdf.ippt.rekomendasi-teknis', [
            'rekomendasi' => $rekomendasi,
            'permohonan' => $rekomendasi->permohonan,
            'pemohon' => $rekomendasi->permohonan->pemohon,
            'bap' => $bap,
            'logoPath' => public_path('images/logo.png'),
        ])
            ->setPaper('a4')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);
    }

    public function store(RekomendasiTeknis $rekomendasi): string
    {
        $filename = sprintf(
            '%s_rekomendasi-teknis.pdf',
            strtolower(str_replace(['/', '\\'], '-', $rekomendasi->nomor_rekomendasi ?: 'rekomendasi-' . $rekomendasi->id))
        );
        $path = 'rekomendasi-teknis/generated/' . $filename;
        Storage::disk('private')->put($path, $this->generate($rekomendasi)->output());
        return $path;
    }
}
