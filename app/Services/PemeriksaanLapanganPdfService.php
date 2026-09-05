<?php

namespace App\Services;

use App\Models\PemeriksaanLapangan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PemeriksaanLapanganPdfService
{
    public function generate(PemeriksaanLapangan $pemeriksaan)
    {
        $pemeriksaan->load([
            'permohonan.pemohon',
            'dibuatOleh',
            'difinalisasiOleh',
        ]);

        return Pdf::loadView('pdf.ippt.bap-pemeriksaan-lapangan', [
            'pemeriksaan' => $pemeriksaan,
            'permohonan' => $pemeriksaan->permohonan,
            'pemohon' => $pemeriksaan->permohonan->pemohon,
            'logoPath' => public_path('images/logo.png'),
            'fotoDataUris' => $this->photoDataUris($pemeriksaan),
        ])
            ->setPaper('a4')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);
    }

    public function store(PemeriksaanLapangan $pemeriksaan): string
    {
        $filename = sprintf(
            '%s_bap-pemeriksaan-lapangan.pdf',
            strtolower(str_replace(['/', '\\'], '-', $pemeriksaan->nomor_bap))
        );
        $path = 'pemeriksaan-lapangan/bap/' . $filename;

        Storage::disk('private')->put($path, $this->generate($pemeriksaan)->output());

        return $path;
    }

    private function photoDataUris(PemeriksaanLapangan $pemeriksaan): array
    {
        return collect($pemeriksaan->foto_lapangan ?? [])
            ->filter(fn (string $path): bool => Storage::disk('private')->exists($path))
            ->map(function (string $path): string {
                $mime = Storage::disk('private')->mimeType($path) ?: 'image/jpeg';

                return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('private')->get($path));
            })
            ->values()
            ->all();
    }
}
