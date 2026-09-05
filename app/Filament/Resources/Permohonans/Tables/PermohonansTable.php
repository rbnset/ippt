<?php

namespace App\Filament\Resources\Permohonans\Tables;

use App\Enums\StatusPermohonan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermohonansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_permohonan')
                    ->label('Nomor Permohonan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pemohon.nama')
                    ->label('Pemohon')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal_permohonan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('lokasi_tanah')
                    ->label('Lokasi Tanah')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('luas_tanah')
                    ->label('Luas')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' m²')
                    ->sortable(),

                TextColumn::make('penggunaan_sekarang')
                    ->label('Penggunaan Saat Ini')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('penggunaan_dimohonkan')
                    ->label('Penggunaan Dimohonkan')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                // getLabel() & getColor() dari enum otomatis dipakai,
                // tidak perlu formatStateUsing() atau colors() manual lagi.

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusPermohonan::class),
                // otomatis ambil semua case + label dari enum

                Filter::make('permohonan_bulan_ini')
                    ->label('Bulan Ini')
                    ->query(
                        fn(Builder $query): Builder => $query
                            ->whereMonth('tanggal_permohonan', now()->month)
                            ->whereYear('tanggal_permohonan', now()->year)
                    ),
            ])

            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }
}
