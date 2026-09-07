<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pemohons\Pages;

use App\Filament\Resources\Pemohons\PemohonResource;
use App\Models\Pemohon;
use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPemohons extends ListRecords
{
    protected static string $resource = PemohonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Status tabs membuat antrean verifikasi langsung terbaca tanpa
     * memaksa petugas membuka filter table terlebih dahulu.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->badge(fn (): int => Pemohon::query()->count())
                ->icon('heroicon-m-users'),

            'terverifikasi' => Tab::make('Terverifikasi')
                ->badge(fn (): int => Pemohon::query()->where('status_verifikasi', 'terverifikasi')->count())
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status_verifikasi', 'terverifikasi')),

            'menunggu_verifikasi' => Tab::make('Menunggu Verifikasi')
                ->badge(fn (): int => Pemohon::query()->where('status_verifikasi', 'menunggu_verifikasi')->count())
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status_verifikasi', 'menunggu_verifikasi')),

            'menunggu_perubahan' => Tab::make('Menunggu Izin Perubahan')
                ->badge(fn (): int => Pemohon::query()->where('status_verifikasi', 'menunggu_perubahan')->count())
                ->icon('heroicon-m-pencil-square')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status_verifikasi', 'menunggu_perubahan')),

            'verifikasi_ditolak' => Tab::make('Verifikasi Ditolak')
                ->badge(fn (): int => Pemohon::query()->where('status_verifikasi', 'perlu_perbaikan')->count())
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status_verifikasi', 'perlu_perbaikan')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'menunggu_verifikasi';
    }
}
