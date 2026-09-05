<?php

namespace App\Filament\Resources\Pemohons\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PemohonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Identitas Pemohon')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('nama')
                                    ->label('Nama'),

                                TextEntry::make('jenis_pemohon')
                                    ->label('Jenis Pemohon')
                                    ->badge()
                                    ->formatStateUsing(
                                        fn(string $state): string => match ($state) {
                                            'perorangan' => 'Perorangan',
                                            'badan' => 'Badan',
                                            default => ucfirst($state),
                                        }
                                    ),

                                TextEntry::make('nik')
                                    ->label('NIK')
                                    ->placeholder('-'),

                                TextEntry::make('nib')
                                    ->label('NIB')
                                    ->placeholder('-'),

                                TextEntry::make('npwp')
                                    ->label('NPWP')
                                    ->placeholder('-'),

                                TextEntry::make('nomor_telepon')
                                    ->label('Nomor Telepon'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('Alamat')
                    ->schema([
                        TextEntry::make('alamat')
                            ->label('Alamat'),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('kelurahan')
                                    ->label('Kelurahan')
                                    ->placeholder('-'),

                                TextEntry::make('kecamatan')
                                    ->label('Kecamatan')
                                    ->placeholder('-'),

                                TextEntry::make('kota')
                                    ->label('Kota'),
                            ]),
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
