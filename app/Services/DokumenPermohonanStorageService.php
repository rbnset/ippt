<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DokumenPermohonan;
use App\Models\Permohonan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DokumenPermohonanStorageService
{
    private const DISK = 'private';

    /**
     * Store an uploaded requirement document using a predictable, human-readable
     * path while keeping the actual files on the private disk.
     */
    public function organize(
        Permohonan $permohonan,
        DokumenPermohonan $document,
        string $uploadedPath,
    ): string {
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($uploadedPath)) {
            throw new \RuntimeException('File dokumen yang diunggah tidak ditemukan.');
        }

        $pemohonName = Str::slug($permohonan->pemohon?->nama ?: 'pemohon', '_');
        $jenis = Str::slug($document->jenis_dokumen->value, '_');
        $extension = strtolower(pathinfo($uploadedPath, PATHINFO_EXTENSION));
        $baseName = $jenis . '_' . $pemohonName;

        $revision = $this->nextRevision($permohonan, $document->jenis_dokumen->value);
        $fileName = $revision === 0
            ? $baseName . ($extension ? '.' . $extension : '')
            : $baseName . '_Revisi-' . str_pad((string) $revision, 2, '0', STR_PAD_LEFT) . ($extension ? '.' . $extension : '');

        $directory = 'permohonan/' . $pemohonName . '/dokumen-persyaratan';
        $target = $directory . '/' . $fileName;

        // Avoid accidental collisions without changing the documented revision
        // convention. This is mainly a safeguard for manually restored files.
        $counter = 1;
        while ($disk->exists($target)) {
            $suffix = '_copy-' . $counter++;
            $target = $directory . '/' . $baseName . $suffix . ($extension ? '.' . $extension : '');
        }

        if ($uploadedPath !== $target) {
            $disk->move($uploadedPath, $target);
        }

        return $target;
    }

    public function nextRevision(Permohonan $permohonan, string $jenisDokumen): int
    {
        $count = $permohonan->dokumenPermohonan()
            ->where('jenis_dokumen', $jenisDokumen)
            ->count();

        // The document record is created before this service moves the file.
        // Exclude the current record so the first upload remains unversioned
        // and the first replacement becomes Revisi-01.
        return max(0, $count - 1);
    }

    public function disk(): string
    {
        return self::DISK;
    }
}
