<?php

namespace App\Filament\Resources\Permohonans\Schemas;

use App\Enums\StatusPermohonan;
use App\Models\Pemohon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PermohonanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Permohonan')
                    ->description('Informasi dasar pengajuan IPPT.')
                    ->icon(Heroicon::DocumentText)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nomor_permohonan')
                                    ->label('Nomor Permohonan')
                                    ->required()
                                    ->maxLength(30)
                                    ->unique(ignoreRecord: true),

                                DatePicker::make('tanggal_permohonan')
                                    ->label('Tanggal Permohonan')
                                    ->required()
                                    ->default(now())
                                    ->native(false),

                                Select::make('pemohon_id')
                                    ->label('Pemohon')
                                    ->relationship(
                                        name: 'pemohon',
                                        titleAttribute: 'nama'
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Data Tanah')
                    ->description('Informasi tanah yang dimohonkan perubahan penggunaannya.')
                    ->icon(Heroicon::MapPin)
                    ->schema([
                        TextInput::make('lokasi_tanah')
                            ->label('Lokasi Tanah')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('luas_tanah')
                                    ->label('Luas Tanah')
                                    ->numeric()
                                    ->suffix('m²')
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(99999999.99),

                                TextInput::make('nomor_hak')
                                    ->label('Nomor Hak')
                                    ->maxLength(50),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('penggunaan_sekarang')
                                    ->label('Penggunaan Tanah Saat Ini')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Contoh: Tanah Pertanian'),

                                TextInput::make('penggunaan_dimohonkan')
                                    ->label('Penggunaan Tanah yang Dimohonkan')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Contoh: Rumah Tinggal'),
                            ]),

                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),

                // Hanya admin yang boleh override status manual.
                // Role lain mengubah status lewat action workflow (verifikasi, rekomendasi, dsb),
                // bukan lewat form ini.
                Section::make('Status')
                    ->description('Override status secara manual (khusus admin).')
                    ->icon(Heroicon::Flag)
                    ->visible(fn() => auth()->user()->hasRole('admin'))
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(StatusPermohonan::class)
                            ->native(false)
                            ->required(),
                    ]),
            ]);
    }
}
