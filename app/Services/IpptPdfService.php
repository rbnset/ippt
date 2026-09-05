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
        ]);

        return Pdf::loadView(
            'pdf.ippt.keputusan',
            [
                'keputusan' => $keputusan,
                'permohonan' => $keputusan->permohonan,
                'pemohon' => $keputusan->permohonan->pemohon,
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
        $filename = sprintf(
            '%s_keputusan-ippt.pdf',
            strtolower(str_replace(['/', '\\'], '-', $keputusan->nomor_keputusan ?: 'keputusan-ippt-' . $keputusan->id))
        );

        $path = 'keputusan-ippt/generated/' . $filename;

        Storage::disk('private')->put($path, $this->generate($keputusan)->output());

        return $path;
    }

}
