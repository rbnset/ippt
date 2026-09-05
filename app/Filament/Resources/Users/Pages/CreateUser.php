<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Pengguna berhasil dibuat')
            ->body(
                "Pengguna {$this->record->name} berhasil ditambahkan."
            )
            ->send();
    }
}
