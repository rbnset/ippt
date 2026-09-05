<?php

namespace App\Filament\Resources\Permohonans\Pages;

use App\Filament\Resources\Permohonans\PermohonanResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPermohonan extends EditRecord
{
    protected static string $resource = PermohonanResource::class;

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('Permohonan berhasil diperbarui')
            ->body(
                "Permohonan \"{$this->record->nomor_permohonan}\" berhasil diperbarui."
            )
            ->send();
    }
}
