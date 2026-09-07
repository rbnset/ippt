<?php

namespace App\Services;

use App\Models\PemeriksaanLapangan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class PemeriksaanLapanganPdfService
{
    public function generate(PemeriksaanLapangan $pemeriksaan)
    {
        $pemeriksaan->load(['permohonan.pemohon', 'dibuatOleh', 'difinalisasiOleh', 'ketuaTim']);
        $teamMembers = User::query()->whereIn('id', $pemeriksaan->anggota_tim_ids ?? [])->orderBy('name')->get();

        return Pdf::loadView('pdf.ippt.bap-pemeriksaan-lapangan', [
            'pemeriksaan' => $pemeriksaan,
            'permohonan' => $pemeriksaan->permohonan,
            'pemohon' => $pemeriksaan->permohonan->pemohon,
            'logoPath' => public_path('images/logo.png'),
            'photos' => $this->photos($pemeriksaan),
            'teamMembers' => $teamMembers,
        ])
            ->setPaper('a4')
            ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);
    }

    public function store(PemeriksaanLapangan $pemeriksaan): string
    {
        $filename = sprintf(
            '%s_bap-pemeriksaan-lapangan-v%s.pdf',
            strtolower(str_replace(['/', '\\'], '-', $pemeriksaan->nomor_bap)),
            $pemeriksaan->versi ?: 1,
        );
        $path = 'pemeriksaan-lapangan/bap/' . $filename;
        Storage::disk('private')->put($path, $this->generate($pemeriksaan)->output());
        return $path;
    }

    private function photos(PemeriksaanLapangan $pemeriksaan): array
    {
        return collect($pemeriksaan->foto_lapangan ?? [])
            ->map(function ($photo): ?array {
                $path = is_array($photo) ? ($photo['path'] ?? null) : $photo;
                if (! $path || ! Storage::disk('private')->exists($path)) return null;

                return [
                    'path' => $path,
                    'caption' => is_array($photo) ? ($photo['caption'] ?? 'Dokumentasi lapangan') : 'Dokumentasi lapangan',
                    'jenis' => is_array($photo) ? ($photo['jenis'] ?? null) : null,
                    'data_uri' => 'data:' . (Storage::disk('private')->mimeType($path) ?: 'image/jpeg') . ';base64,' . base64_encode(Storage::disk('private')->get($path)),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
