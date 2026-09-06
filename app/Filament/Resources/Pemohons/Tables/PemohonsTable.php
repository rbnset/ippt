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

                TextColumn::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'terverifikasi' => 'Terverifikasi',
                        'menunggu_verifikasi' => 'Menunggu Verifikasi',
                        'perlu_perbaikan' => 'Perlu Perbaikan',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'terverifikasi' => 'success',
                        'perlu_perbaikan' => 'warning',
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
                ViewAction::make(),
                EditAction::make(),
                Action::make('verifikasi')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => auth()->user()?->hasAnyRole([UserRole::ADMIN, UserRole::STAFF]) && $record->status_verifikasi !== 'terverifikasi')
                    ->action(function ($record): void {
                        $record->update([
                            'status_verifikasi' => 'terverifikasi',
                            'catatan_verifikasi' => null,
                            'diverifikasi_oleh' => auth()->id(),
                            'diverifikasi_pada' => now(),
                        ]);
                        Notification::make()->success()->title('Data pemohon terverifikasi')->body('Pemohon sekarang dapat mengajukan permohonan IPPT.')->send();
                    }),
                Action::make('minta_perbaikan')
                    ->label('Minta Perbaikan')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('catatan_verifikasi')->label('Catatan perbaikan')->required()->rows(4),
                    ])
                    ->visible(fn ($record): bool => auth()->user()?->hasAnyRole([UserRole::ADMIN, UserRole::STAFF]) && $record->status_verifikasi !== 'terverifikasi')
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'status_verifikasi' => 'perlu_perbaikan',
                            'catatan_verifikasi' => $data['catatan_verifikasi'],
                            'diverifikasi_oleh' => auth()->id(),
                            'diverifikasi_pada' => now(),
                        ]);
                        Notification::make()->warning()->title('Perbaikan diminta')->body('Catatan perbaikan tersimpan dan dapat dilihat pemohon.')->send();
                    }),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }
}
