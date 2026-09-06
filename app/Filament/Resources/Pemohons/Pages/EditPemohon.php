<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pemohons\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Pemohons\PemohonResource;
use App\Services\PemohonVerificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPemohon extends EditRecord
{
    protected static string $resource = PemohonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setujuiVerifikasi')
                ->label('Setujui & Verifikasi')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->hasAnyRole([UserRole::ADMIN, UserRole::STAFF])
                    && $this->record->status_verifikasi !== 'terverifikasi')
                ->requiresConfirmation()
                ->modalHeading('Verifikasi Data Pemohon')
                ->modalDescription('Pastikan identitas, kontak, dan alamat telah diperiksa terhadap dokumen pendukung sebelum menyetujui.')
                ->modalSubmitActionLabel('Ya, Verifikasi')
                ->action(function (): void {
                    app(PemohonVerificationService::class)->approve($this->record);
                }),

            Action::make('mintaPerbaikan')
                ->label('Tolak & Minta Perbaikan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => auth()->user()?->hasAnyRole([UserRole::ADMIN, UserRole::STAFF])
                    && $this->record->status_verifikasi !== 'terverifikasi')
                ->form([
                    Textarea::make('alasan')
                        ->label('Alasan Perbaikan')
                        ->required()
                        ->rows(6)
                        ->maxLength(2000)
                        ->placeholder('Jelaskan data yang tidak sesuai dan apa yang harus diperbaiki pemohon.'),
                ])
                ->modalHeading('Tolak Data & Minta Perbaikan')
                ->modalSubmitActionLabel('Kirim Permintaan Perbaikan')
                ->action(function (array $data): void {
                    app(PemohonVerificationService::class)->reject($this->record, $data['alasan']);
                }),
        ];
    }

}
