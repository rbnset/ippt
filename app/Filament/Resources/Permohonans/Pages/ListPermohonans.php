<?php

namespace App\Filament\Resources\Permohonans\Pages;

use App\Enums\UserRole;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Permohonans\PermohonanResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPermohonans extends ListRecords
{
    protected static string $resource = PermohonanResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        if (! $user?->hasRole(UserRole::PEMOHON)) {
            return [
                CreateAction::make()->label('Tambah Permohonan'),
            ];
        }

        $pemohon = $user->pemohon()->first();

        if (! $pemohon) {
            return [
                Action::make('buat_permohonan')
                    ->label('Ajukan Permohonan')
                    ->icon('heroicon-o-plus-circle')
                    ->action(function (): void {
                        Notification::make()
                            ->warning()
                            ->title('Data pemohon belum diisi')
                            ->body('Lengkapi data pemohon di Dasbor terlebih dahulu. Setelah dikirim dan diverifikasi petugas, Anda dapat mengajukan IPPT.')
                            ->persistent()
                            ->send();
                    }),
                Action::make('lengkapi_data_pemohon')
                    ->label('Lengkapi Data Pemohon')
                    ->icon('heroicon-o-user-circle')
                    ->color('gray')
                    ->url(Dashboard::getUrl()),
            ];
        }

        if (! $pemohon->isTerverifikasi()) {
            return [
                Action::make('buat_permohonan')
                    ->label('Ajukan Permohonan')
                    ->icon('heroicon-o-plus-circle')
                    ->action(function () use ($pemohon): void {
                        $status = $pemohon->status_verifikasi === 'perlu_perbaikan'
                            ? 'Data pemohon perlu diperbaiki sesuai catatan petugas.'
                            : 'Data pemohon sedang menunggu verifikasi petugas.';

                        Notification::make()
                            ->warning()
                            ->title('Permohonan belum dapat diajukan')
                            ->body("{$status} Setelah data terverifikasi, tombol pengajuan akan tersedia.")
                            ->persistent()
                            ->send();
                    }),
                Action::make('buka_data_pemohon')
                    ->label($pemohon->status_verifikasi === 'perlu_perbaikan' ? 'Perbaiki Data Pemohon' : 'Lihat Status Data')
                    ->icon('heroicon-o-user-circle')
                    ->color('gray')
                    ->url(Dashboard::getUrl()),
            ];
        }

        return [
            CreateAction::make()->label('Ajukan Permohonan'),
        ];
    }
}
