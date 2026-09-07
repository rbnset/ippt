<?php

namespace App\Filament\Resources\Permohonans\Pages;

use App\Filament\Resources\Permohonans\PermohonanResource;
use App\Enums\UserRole;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPermohonan extends ViewRecord
{
    protected static string $resource = PermohonanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => ! auth()->user()->hasRole(UserRole::KABID)),
        ];
    }
}
