<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title('Pengguna berhasil diperbarui')
            ->body(
                "Data {$this->record->name} berhasil diperbarui."
            )
            ->send();
    }
}
