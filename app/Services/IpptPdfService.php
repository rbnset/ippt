<?php

namespace App\Services;

use App\Models\KeputusanIppt;
use Barryvdh\DomPDF\Facade\Pdf;

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
}
