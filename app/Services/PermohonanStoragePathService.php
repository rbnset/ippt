<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permohonan;
use Illuminate\Support\Str;

/**
 * Canonical private-storage paths for all files belonging to a Permohonan.
 *
 * The path intentionally starts at `permohonan/` instead of the public web
 * root. Files contain personal/administrative data and are served through the
 * application's authorized private-file endpoints.
 */
class PermohonanStoragePathService
{
    public function pemohonSlug(Permohonan $permohonan): string
    {
        return Str::slug($permohonan->pemohon?->nama ?: 'pemohon', '_');
    }

    public function directory(Permohonan $permohonan, string $category): string
    {
        return 'permohonan/' . $this->pemohonSlug($permohonan) . '/' . trim($category, '/');
    }

    public function filename(string $prefix, string $identifier, string $extension = 'pdf'): string
    {
        $safeIdentifier = Str::slug(str_replace(['/', '\\'], '-', $identifier), '-');
        $safePrefix = Str::slug($prefix, '-');
        $extension = strtolower(ltrim($extension, '.'));

        return $safePrefix . '-' . $safeIdentifier . '.' . $extension;
    }

    public function path(Permohonan $permohonan, string $category, string $prefix, string $identifier, string $extension = 'pdf'): string
    {
        return $this->directory($permohonan, $category) . '/' . $this->filename($prefix, $identifier, $extension);
    }
}
