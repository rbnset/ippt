<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

trait HasFileViewAction
{
    /**
     * Aksi "Lihat File" yang seragam untuk semua RelationManager.
     * Menggantikan pola $this->js("window.open(...)") yang berulang.
     *
     * Butuh Storage::disk($disk)->buildTemporaryUrlsUsing() sudah didaftarkan
     * di AppServiceProvider (lihat catatan setup) supaya temporaryUrl() bekerja
     * pada disk 'local'/'private'.
     */
    protected static function fileViewAction(
        string $attribute = 'lokasi_file',
        string $disk = 'private',
        string $label = 'Lihat File',
        string $name = 'lihat_file',
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->visible(fn(Model $record): bool => filled($record->{$attribute}))
            ->disabled(fn(Model $record): bool => ! Storage::disk($disk)->exists($record->{$attribute}))
            ->tooltip(fn(Model $record): ?string => Storage::disk($disk)->exists($record->{$attribute})
                ? null
                : 'File tidak ditemukan di storage')
            ->url(function (Model $record) use ($attribute, $disk): ?string {
                if (! Storage::disk($disk)->exists($record->{$attribute})) {
                    return null;
                }

                return Storage::disk($disk)->temporaryUrl(
                    $record->{$attribute},
                    now()->addMinutes(10),
                );
            })
            ->openUrlInNewTab();
    }
}
