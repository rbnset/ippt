<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Filament\Resources\Permohonans\PermohonanResource;
use App\Models\DokumenPermohonan;
use App\Models\Permohonan;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class IpptWorkflowNotificationService
{
    public function __construct(
        private readonly FilamentDatabaseNotificationService $databaseNotifications,
    ) {
    }

    public function pemohon(Permohonan $permohonan, string $title, string $body, string $level = 'info', ?Action $action = null): void
    {
        $user = $permohonan->loadMissing('pemohon.user')->pemohon?->user;

        if ($user) {
            $this->send($user, $title, $body, $level, $action);
        }
    }

    public function users(iterable $users, string $title, string $body, string $level = 'info', ?Action $action = null): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                $this->send($user, $title, $body, $level, $action);
            }
        }
    }

    public function roles(array $roles, string $title, string $body, string $level = 'info', ?Action $action = null): void
    {
        $users = User::query()
            ->whereIn('role', array_map(
                fn ($role) => $role instanceof UserRole ? $role->value : $role,
                $roles,
            ))
            ->get();

        foreach ($users as $user) {
            $this->send($user, $title, $body, $level, $action);
        }
    }

    /**
     * Notify the pemohon that a specific document was rejected, including the
     * rejection note and a direct action back to the related application.
     */
    public function pemohonDocumentRejected(DokumenPermohonan $document): void
    {
        $document->loadMissing('permohonan.pemohon.user');
        $permohonan = $document->permohonan;

        if (! $permohonan) {
            return;
        }

        $action = Action::make('perbaikiDokumen')
            ->label('Perbaiki Dokumen')
            ->icon('heroicon-o-arrow-up-tray')
            ->button()
            ->url($this->permohonanUrl($permohonan))
            ->markAsRead();

        $this->pemohon(
            $permohonan,
            'Dokumen perlu diperbaiki',
            sprintf(
                '%s pada permohonan %s ditolak. Catatan petugas: %s',
                $document->jenis_dokumen->getLabel(),
                $permohonan->nomor_permohonan,
                $document->catatan ?: 'Silakan periksa kembali dokumen dan unggah berkas yang sesuai.',
            ),
            'danger',
            $action,
        );
    }

    /**
     * Notify Admin and Staff when a rejected document has been replaced.
     */
    public function rolesDocumentReuploaded(DokumenPermohonan $document): void
    {
        $document->loadMissing('permohonan.pemohon');
        $permohonan = $document->permohonan;

        if (! $permohonan) {
            return;
        }

        $action = Action::make('periksaDokumen')
            ->label('Periksa Dokumen')
            ->icon('heroicon-o-document-check')
            ->button()
            ->url($this->permohonanUrl($permohonan))
            ->markAsRead();

        $this->roles(
            [UserRole::ADMIN, UserRole::STAFF],
            'Dokumen IPPT diperbaiki',
            sprintf(
                '%s mengunggah ulang %s pada permohonan %s. Catatan pemohon: %s. Dokumen menunggu verifikasi.',
                $permohonan->pemohon?->nama ?? 'Pemohon',
                $document->jenis_dokumen->getLabel(),
                $permohonan->nomor_permohonan,
                $document->catatan ?: 'Tidak ada catatan tambahan.',
            ),
            'warning',
            $action,
        );
    }

    /**
     * Notify the pemohon that their replacement upload was recorded.
     */
    public function pemohonDocumentReuploaded(DokumenPermohonan $document): void
    {
        $document->loadMissing('permohonan.pemohon.user');
        $permohonan = $document->permohonan;

        if (! $permohonan) {
            return;
        }

        $action = Action::make('lihatDokumen')
            ->label('Lihat Permohonan')
            ->icon('heroicon-o-document-text')
            ->button()
            ->url($this->permohonanUrl($permohonan))
            ->markAsRead();

        $this->pemohon(
            $permohonan,
            'Dokumen berhasil dikirim ulang',
            sprintf(
                '%s untuk permohonan %s telah dikirim ulang dan menunggu verifikasi. Catatan Anda: %s',
                $document->jenis_dokumen->getLabel(),
                $permohonan->nomor_permohonan,
                $document->catatan ?: 'Tidak ada catatan tambahan.',
            ),
            'success',
            $action,
        );
    }

    public function applicationAction(Permohonan $permohonan, string $label = 'Buka Permohonan', ?string $relation = null): Action
    {
        $url = $this->permohonanUrl($permohonan, $relation);

        return Action::make('bukaPermohonan')
            ->label($label)
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->button()
            ->url($url)
            ->markAsRead();
    }

    private function send(User $user, string $title, string $body, string $level, ?Action $action = null): void
    {
        $notification = Notification::make()
            ->title($title)
            ->body($body);

        match ($level) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            default => $notification->info(),
        };

        if ($action) {
            $notification->actions([$action]);
        }

        // Keep the synchronous writer: this application intentionally does not
        // require a queue worker for the in-app notification bell.
        $this->databaseNotifications->send($user, $notification);
    }

    private function permohonanUrl(Permohonan $permohonan, ?string $relation = null): string
    {
        $url = PermohonanResource::getUrl('view', ['record' => $permohonan]);

        if ($relation) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query(['relation' => $relation]);
        }

        return $url;
    }
}
