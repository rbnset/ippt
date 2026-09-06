<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusDokumen;
use App\Enums\StatusPermohonan;
use App\Models\DokumenPermohonan;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class DokumenPermohonanWorkflowService
{
    /**
     * Return the latest submission for each document type.
     *
     * @return array<string, DokumenPermohonan>
     */
    public function latestByType(Permohonan $permohonan): array
    {
        return $permohonan->dokumenPermohonan()
            ->orderByDesc('id')
            ->get()
            ->unique(fn (DokumenPermohonan $document): string => $document->jenis_dokumen->value)
            ->keyBy(fn (DokumenPermohonan $document): string => $document->jenis_dokumen->value)
            ->all();
    }


    public function isLatestSubmission(DokumenPermohonan $document): bool
    {
        $latest = $document->permohonan?->dokumenPermohonan()
            ->where('jenis_dokumen', $document->jenis_dokumen->value)
            ->latest('id')
            ->first();

        return $latest?->id === $document->id;
    }

    /**
     * A pemohon may only submit a replacement when the latest submission
     * for that document type has been rejected.
     */
    public function canPemohonUpload(Permohonan $permohonan, string $jenisDokumen): bool
    {
        $latest = $this->latestByType($permohonan)[$jenisDokumen] ?? null;

        return $latest?->status === StatusDokumen::Ditolak;
    }

    /**
     * Staff/admin may repair an absent document, or replace a rejected one,
     * but may never overwrite a pending/accepted submission through this flow.
     */
    public function canStaffUpload(Permohonan $permohonan, string $jenisDokumen): bool
    {
        $latest = $this->latestByType($permohonan)[$jenisDokumen] ?? null;

        return $latest === null || $latest->status === StatusDokumen::Ditolak;
    }

    public function uploadReplacement(
        Permohonan $permohonan,
        DokumenPermohonan $rejectedDocument,
        string $path,
        ?User $uploader = null,
    ): DokumenPermohonan {
        if ($rejectedDocument->permohonan_id !== $permohonan->id) {
            throw new \InvalidArgumentException('Dokumen tidak termasuk dalam permohonan ini.');
        }

        if ($rejectedDocument->status !== StatusDokumen::Ditolak) {
            throw new \DomainException('Dokumen hanya dapat diunggah ulang jika status sebelumnya ditolak.');
        }

        if (! $this->isLatestSubmission($rejectedDocument)) {
            throw new \DomainException('Dokumen ini bukan pengajuan terakhir untuk jenis dokumen tersebut.');
        }

        return DB::transaction(function () use ($permohonan, $rejectedDocument, $path, $uploader): DokumenPermohonan {
            $disk = Storage::disk('private');
            $size = $disk->exists($path) ? $disk->size($path) : null;

            $document = DokumenPermohonan::create([
                'permohonan_id' => $permohonan->id,
                'diunggah_oleh' => $uploader?->id,
                'jenis_dokumen' => $rejectedDocument->jenis_dokumen,
                'nama_file' => basename($path),
                'lokasi_file' => $path,
                'tipe_file' => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                'ukuran_file' => $size,
                'status' => StatusDokumen::Menunggu,
                'catatan' => null,
            ]);

            // If every previous rejection has now been replaced, the application
            // returns to verification. Otherwise it remains returned.
            $hasOtherRejection = collect($this->latestByType($permohonan->fresh()))
                ->contains(fn (DokumenPermohonan $item): bool => $item->status === StatusDokumen::Ditolak);

            $permohonan->update([
                'status' => $hasOtherRejection
                    ? StatusPermohonan::Dikembalikan
                    : StatusPermohonan::Verifikasi,
            ]);

            return $document;
        });
    }

    public function notifyPemohonRejected(DokumenPermohonan $document): void
    {
        $user = $document->permohonan?->pemohon?->user;

        if (! $user) {
            return;
        }

        Notification::make()
            ->danger()
            ->title('Dokumen perlu diperbaiki')
            ->body(sprintf(
                '%s pada permohonan %s ditolak. Alasan: %s',
                $document->jenis_dokumen->getLabel(),
                $document->permohonan->nomor_permohonan,
                $document->catatan ?: 'Silakan periksa kembali dokumen dan unggah berkas yang sesuai.',
            ))
            ->sendToDatabase($user, isEventDispatched: true);
    }
}
