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
        $permohonan = $pemeriksaan->loadMissing('permohonan.pemohon')->permohonan;
        if (! $permohonan) {
            throw new \RuntimeException('Permohonan untuk BAP tidak ditemukan.');
        }

        $path = app(\App\Services\PermohonanStoragePathService::class)->path(
            $permohonan,
            'pemeriksaan-lapangan',
            'bap-v' . ($pemeriksaan->versi ?: 1),
            $pemeriksaan->nomor_bap ?: 'bap-' . $pemeriksaan->id,
        );
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
