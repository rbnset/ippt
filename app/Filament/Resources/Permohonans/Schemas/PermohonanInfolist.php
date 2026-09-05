<?php

namespace App\Filament\Resources\Permohonans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PermohonanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Permohonan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('nomor_permohonan')
                                    ->label('Nomor Permohonan'),

                                TextEntry::make('tanggal_permohonan')
                                    ->label('Tanggal Permohonan')
                                    ->date('d M Y'),

                                TextEntry::make('pemohon.nama')
                                    ->label('Pemohon'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),
                            ]),
                    ]),

                Section::make('Data Tanah')
                    ->schema([
                        TextEntry::make('lokasi_tanah')
                            ->label('Lokasi Tanah'),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('luas_tanah')
                                    ->label('Luas Tanah')
                                    ->suffix(' m²'),

                                TextEntry::make('nomor_hak')
                                    ->label('Nomor Hak')
                                    ->placeholder('-'),

                                TextEntry::make('penggunaan_sekarang')
                                    ->label('Penggunaan Saat Ini'),

                                TextEntry::make('penggunaan_dimohonkan')
                                    ->label('Penggunaan Dimohonkan'),
                            ]),

                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Informasi Sistem')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dibuat')
                                    ->dateTime('d M Y H:i'),

                                TextEntry::make('updated_at')
                                    ->label('Terakhir Diubah')
                                    ->dateTime('d M Y H:i'),
                            ]),
                    ]),
            ]);
    }
}
