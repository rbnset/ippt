<?php

namespace App\Services;

use App\Models\KeputusanIppt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class IpptPdfService
{
    public function generate(KeputusanIppt $keputusan)
    {
        $keputusan->load([
            'permohonan.pemohon',
            'disusunOleh',
            'disetujuiOleh',
            'permohonan.rekomendasiTeknis',
            'permohonan.risalahPertimbangan',
        ]);

        return Pdf::loadView(
            'pdf.ippt.keputusan',
            [
                'keputusan' => $keputusan,
                'permohonan' => $keputusan->permohonan,
                'pemohon' => $keputusan->permohonan->pemohon,
                'rekomendasi' => $keputusan->permohonan->rekomendasiTeknis,
                'risalah' => $keputusan->permohonan->risalahPertimbangan,
            ]
        )
            ->setPaper('a4')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);
    }
    public function store(KeputusanIppt $keputusan): string
    {
        $permohonan = $keputusan->loadMissing('permohonan.pemohon')->permohonan;
        if (! $permohonan) {
            throw new \RuntimeException('Permohonan untuk keputusan IPPT tidak ditemukan.');
        }

        $path = app(\App\Services\PermohonanStoragePathService::class)->path(
            $permohonan,
            'keputusan-ippt',
            'keputusan',
            $keputusan->nomor_keputusan ?: 'keputusan-ippt-' . $keputusan->id,
        );

        Storage::disk('private')->put($path, $this->generate($keputusan)->output());

        return $path;
    }

}
