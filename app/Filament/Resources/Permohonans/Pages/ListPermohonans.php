<?php

namespace App\Filament\Resources\Permohonans\Pages;

use App\Filament\Resources\Permohonans\PermohonanResource;
use App\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermohonans extends ListRecords
{
    protected static string $resource = PermohonanResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        if ($user?->hasRole(UserRole::PEMOHON) && ! $user->pemohon()->where('status_verifikasi', 'terverifikasi')->exists()) {
            return [
                Action::make('lengkapi_data_pemohon')
                    ->label('Lengkapi Data Pemohon')
                    ->icon('heroicon-o-user-circle')
                    ->url(route('pemohon.profil')),
            ];
        }

        return [
            CreateAction::make()->label('Tambah Permohonan'),
        ];
    }
}
