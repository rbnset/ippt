<?php

namespace App\Filament\Resources\Permohonans\Pages;

use App\Filament\Resources\Permohonans\PermohonanResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePermohonan extends CreateRecord
{
    protected static string $resource = PermohonanResource::class;

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Permohonan berhasil dibuat')
            ->body(
                "Permohonan \"{$this->record->nomor_permohonan}\" berhasil dibuat."
            )
            ->send();
    }
}
