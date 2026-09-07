<?php

namespace App\Services;

use App\Enums\StatusPemeriksaan;
use App\Models\RekomendasiTeknis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Services\PermohonanStoragePathService;

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
        $permohonan = $rekomendasi->loadMissing('permohonan.pemohon')->permohonan;
        if (! $permohonan) {
            throw new \RuntimeException('Permohonan untuk rekomendasi teknis tidak ditemukan.');
        }

        $path = app(PermohonanStoragePathService::class)->path(
            $permohonan,
            'rekomendasi-teknis',
            'rekomendasi',
            $rekomendasi->nomor_rekomendasi ?: 'rekomendasi-' . $rekomendasi->id,
        );
        Storage::disk('private')->put($path, $this->generate($rekomendasi)->output());
        return $path;
    }
}
