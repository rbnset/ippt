<?php

namespace App\Filament\Resources\Pemohons\Tables;

use App\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PemohonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jenis_pemohon')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'perorangan' => 'Perorangan',
                        'badan' => 'Badan',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('nib')
                    ->label('NIB')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('npwp')
                    ->label('NPWP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nomor_telepon')
                    ->label('Telepon')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('kecamatan')
                    ->label('Kecamatan')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('kota')
                    ->label('Kota')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nomor_antrian')->label('No. Antrian')->searchable()->sortable()->placeholder('—'),

                TextColumn::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'terverifikasi' => 'Terverifikasi',
                        'menunggu_verifikasi' => 'Menunggu Verifikasi',
                        'perlu_perbaikan' => 'Perlu Perbaikan',
                        'menunggu_perubahan' => 'Menunggu Izin Perubahan',
                        'perlu_perubahan' => 'Perubahan Data Diizinkan',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'terverifikasi' => 'success',
                        'perlu_perbaikan' => 'danger',
                        'menunggu_perubahan' => 'warning',
                        'perlu_perubahan' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('jenis_pemohon')
                    ->label('Jenis Pemohon')
                    ->options([
                        'perorangan' => 'Perorangan',
                        'badan' => 'Badan',
                    ]),
            ])

            ->recordActions([
                Action::make('review')
                    ->label('Tinjau / Verifikasi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->url(fn ($record): string => \App\Filament\Resources\Pemohons\PemohonResource::getUrl('edit', ['record' => $record])),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }
}
