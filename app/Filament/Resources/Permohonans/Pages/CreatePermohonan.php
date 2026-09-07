<?php

namespace App\Filament\Resources\Permohonans\Pages;

use App\Enums\JenisDokumen;
use App\Enums\StatusDokumen;
use App\Enums\StatusPermohonan;
use App\Filament\Resources\Permohonans\PermohonanResource;
use App\Models\DokumenPermohonan;
use App\Services\PermohonanNumberService;
use App\Services\IpptWorkflowNotificationService;
use App\Enums\UserRole;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreatePermohonan extends CreateRecord
{
    protected static string $resource = PermohonanResource::class;

    /** @var array<string, mixed> */
    protected array $uploadedDocuments = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->uploadedDocuments = [
            'ktp' => $data['dokumen_ktp_pemohon'] ?? null,
            'ktp_pemegang_kuasa' => $data['dokumen_ktp_pemegang_kuasa'] ?? null,
            'bukti_hak' => $data['dokumen_bukti_hak'] ?? null,
            'surat_kuasa' => $data['dokumen_surat_kuasa'] ?? null,
            'tidak_sengketa' => $data['dokumen_tidak_sengketa'] ?? null,
            'pbb' => $data['dokumen_pbb'] ?? null,
            'denah_lokasi' => $data['dokumen_denah_lokasi'] ?? null,
        ];

        unset(
            $data['dokumen_ktp_pemohon'],
            $data['dokumen_ktp_pemegang_kuasa'],
            $data['dokumen_bukti_hak'],
            $data['dokumen_surat_kuasa'],
            $data['dokumen_tidak_sengketa'],
            $data['dokumen_pbb'],
            $data['dokumen_denah_lokasi'],
            $data['dokumen_check_info'],
        );

        $user = Auth::user();

        if ($user?->hasRole(UserRole::PEMOHON)) {
            $pemohonId = $user->pemohon()->value('id');

            abort_if(! $pemohonId, 403, 'Akun pemohon belum memiliki data pemohon.');
            abort_unless($user->pemohon()->where('status_verifikasi', 'terverifikasi')->exists(), 403, 'Data pemohon belum terverifikasi.');

            // Jangan percaya nilai pemohon_id dari browser untuk pengajuan online.
            $data['pemohon_id'] = $pemohonId;
        }

        $date = now();
        $data['nomor_permohonan'] = app(PermohonanNumberService::class)->generate($date);
        $data['tanggal_permohonan'] = $date->toDateString();
        $data['status'] = StatusPermohonan::Diajukan;

        if (! ($data['diwakilkan'] ?? false)) {
            $data['nama_pemegang_kuasa'] = null;
            $data['nik_pemegang_kuasa'] = null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->storeRequiredDocuments();

        Notification::make()
            ->success()
            ->title('Permohonan berhasil diajukan')
            ->body("Nomor permohonan {$this->record->nomor_permohonan}. Seluruh dokumen persyaratan telah terhubung dengan permohonan.")
            ->send();

        app(IpptWorkflowNotificationService::class)->roles(
            [UserRole::ADMIN, UserRole::STAFF],
            'Permohonan IPPT baru',
            "Permohonan {$this->record->nomor_permohonan} dari {$this->record->pemohon?->nama} menunggu pemeriksaan.",
            'info',
            app(IpptWorkflowNotificationService::class)->applicationAction($this->record, 'Buka Permohonan'),
        );
        app(IpptWorkflowNotificationService::class)->pemohon(
            $this->record,
            'Permohonan IPPT berhasil diajukan',
            "Permohonan {$this->record->nomor_permohonan} telah diterima dan masuk ke tahap pemeriksaan.",
            'success',
            app(IpptWorkflowNotificationService::class)->applicationAction($this->record, 'Lihat Permohonan'),
        );
    }

    private function storeRequiredDocuments(): void
    {
        $documents = [
            'ktp' => JenisDokumen::Ktp,
            'ktp_pemegang_kuasa' => JenisDokumen::KtpPemegangKuasa,
            'bukti_hak' => JenisDokumen::BuktiHak,
            'surat_kuasa' => JenisDokumen::SuratKuasa,
            'tidak_sengketa' => JenisDokumen::SuratTidakSengketa,
            'pbb' => JenisDokumen::Pbb,
            'denah_lokasi' => JenisDokumen::SitePlan,
        ];

        foreach ($documents as $key => $jenis) {
            $path = $this->uploadedDocuments[$key] ?? null;

            if (! $path) {
                continue;
            }

            $size = Storage::disk('private')->exists($path)
                ? Storage::disk('private')->size($path)
                : null;

            $document = DokumenPermohonan::create([
                'permohonan_id' => $this->record->id,
                'diunggah_oleh' => Auth::id(),
                'jenis_dokumen' => $jenis,
                'nama_file' => basename($path),
                'lokasi_file' => $path,
                'tipe_file' => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                'ukuran_file' => $size,
                'status' => StatusDokumen::Menunggu,
            ]);

            $organizedPath = app(\App\Services\DokumenPermohonanStorageService::class)->organize(
                $this->record->fresh('pemohon'),
                $document,
                $path,
            );

            $document->update([
                'nama_file' => basename($organizedPath),
                'lokasi_file' => $organizedPath,
                'ukuran_file' => Storage::disk('private')->size($organizedPath),
            ]);
        }
    }
}
