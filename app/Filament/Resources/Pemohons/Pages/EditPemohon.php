<?php

namespace App\Filament\Resources\Pemohons\Pages;

use App\Filament\Resources\Pemohons\PemohonResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPemohon extends EditRecord
{
    protected static string $resource = PemohonResource::class;

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('Pemohon berhasil diperbarui')
            ->body(
                "Data pemohon \"{$this->record->nama}\" berhasil diperbarui."
            )
            ->send();
    }
}
