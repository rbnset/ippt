<?php

namespace App\Filament\Resources\Pemohons\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PemohonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Jenis & Identitas Pemohon')
                    ->description('Informasi dasar pemohon IPPT.')
                    ->icon(Heroicon::User)
                    ->schema([
                        Select::make('jenis_pemohon')
                            ->label('Jenis Pemohon')
                            ->options([
                                'perorangan' => 'Perorangan',
                                'badan' => 'Badan Usaha',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('nama')
                            ->label(fn(Get $get): string => $get('jenis_pemohon') === 'badan'
                                ? 'Nama Badan Usaha'
                                : 'Nama Pemohon')
                            ->required()
                            ->maxLength(150)
                            ->autofocus()
                            ->columnSpanFull(),

                        TextInput::make('nik')
                            ->label('NIK')
                            ->placeholder('16 digit NIK')
                            ->length(16)
                            ->regex('/^[0-9]{16}$/')
                            ->prefixIcon('heroicon-o-identification')
                            ->dehydrateStateUsing(fn($state) => filled($state) ? (string) $state : null)
                            ->visible(fn(Get $get): bool => $get('jenis_pemohon') === 'perorangan')
                            ->required(fn(Get $get): bool => $get('jenis_pemohon') === 'perorangan'),

                        TextInput::make('nib')
                            ->label('NIB')
                            ->placeholder('13 digit NIB')
                            ->length(13)
                            ->numeric()
                            ->prefixIcon('heroicon-o-building-office')
                            ->visible(fn(Get $get): bool => $get('jenis_pemohon') === 'badan')
                            ->required(fn(Get $get): bool => $get('jenis_pemohon') === 'badan'),

                        TextInput::make('npwp')
                            ->label('NPWP')
                            ->placeholder('Nomor NPWP')
                            ->maxLength(16)
                            ->numeric()
                            ->prefixIcon('heroicon-o-document-text'),
                    ])
                    ->columns(2),

                Section::make('Kontak')
                    ->description('Nomor telepon dan email aktif untuk korespondensi.')
                    ->icon(Heroicon::Phone)
                    ->schema([
                        TextInput::make('nomor_telepon')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->required()
                            ->maxLength(16)
                            ->placeholder('08xxxxxxxxxx')
                            ->prefixIcon('heroicon-o-phone'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(150)
                            ->placeholder('email@contoh.com')
                            ->prefixIcon('heroicon-o-envelope'),
                    ])
                    ->columns(2),

                Section::make('Alamat')
                    ->description('Alamat lengkap pemohon.')
                    ->icon(Heroicon::MapPin)
                    ->schema([
                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->required()
                            ->rows(3)
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('kelurahan')
                            ->label('Kelurahan')
                            ->maxLength(100),

                        TextInput::make('kecamatan')
                            ->label('Kecamatan')
                            ->maxLength(100),

                        TextInput::make('kota')
                            ->label('Kota')
                            ->required()
                            ->default('Yogyakarta')
                            ->maxLength(100),
                    ])
                    ->columns(3),


                Section::make('Status Verifikasi')
                    ->description('Informasi ini dikendalikan oleh proses verifikasi. Petugas dapat menyetujui atau meminta perbaikan melalui tindakan di halaman ini.')
                    ->icon(Heroicon::ShieldCheck)
                    ->schema([
                        TextInput::make('nomor_antrian')
                            ->label('Nomor Antrian')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('status_verifikasi')
                            ->label('Status')
                            ->options([
                                'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                'perlu_perbaikan' => 'Perlu Perbaikan',
                                'perlu_perubahan' => 'Menunggu Perubahan Data',
                                'terverifikasi' => 'Terverifikasi',
                            ])
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false),
                        Textarea::make('alasan_perubahan')
                            ->label('Alasan Perubahan Data')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(4),
                    ])
                    ->columns(2),

                Section::make('Akun Pengguna')
                    ->description('Hubungan akun dikelola sistem. Satu akun pengguna hanya dapat memiliki satu data pemohon.')
                    ->icon(Heroicon::UserCircle)
                    ->schema([
                        Placeholder::make('akun_terhubung')
                            ->label('Akun yang terhubung')
                            ->content(function (?\App\Models\Pemohon $record): string {
                                $user = $record?->user;

                                return $user
                                    ? "{$user->name} · {$user->email}"
                                    : 'Belum terhubung ke akun pengguna';
                            })
                            ->columnSpanFull(),
                    ]),

            ]);
    }
}
