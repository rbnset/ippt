<?php

namespace App\Filament\Resources\Pemohons\Pages;

use App\Filament\Resources\Pemohons\PemohonResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePemohon extends CreateRecord
{
    protected static string $resource = PemohonResource::class;

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Pemohon berhasil dibuat')
            ->body(
                "Data pemohon \"{$this->record->nama}\" berhasil ditambahkan."
            )
            ->send();
    }
}
