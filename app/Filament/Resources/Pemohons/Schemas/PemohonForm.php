<?php

namespace App\Filament\Resources\Pemohons\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;

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

                Section::make('Akun Pengguna')
                    ->description('Opsional. Hubungkan data pemohon ini dengan akun login supaya pemohon bisa memantau status permohonannya sendiri. Boleh dikosongkan jika pemohon belum atau tidak memiliki akun.')
                    ->icon(Heroicon::UserCircle)
                    ->collapsible()
                    ->schema([
                        Toggle::make('has_account')
                            ->label('Kaitkan dengan akun pengguna')
                            ->live()
                            ->dehydrated(false)
                            ->default(fn(?\App\Models\Pemohon $record): bool => filled($record?->user_id))
                            ->helperText('Aktifkan untuk mencari akun yang sudah ada, atau membuat akun baru.')
                            ->columnSpanFull(),

                        Select::make('user_id')
                            ->label('Akun Pengguna')
                            ->relationship(name: 'user', titleAttribute: 'name')
                            ->getOptionLabelFromRecordUsing(fn(User $record): string => "{$record->name} ({$record->email})")
                            ->searchable(['name', 'email'])
                            ->preload()
                            ->native(false)
                            ->visible(fn(Get $get): bool => (bool) $get('has_account'))
                            ->required(fn(Get $get): bool => (bool) $get('has_account'))
                            ->dehydrated(true)
                            ->dehydrateStateUsing(fn($state, Get $get) => $get('has_account') ? $state : null)
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    ->maxLength(150),

                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->unique('users', 'email'),

                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->required()
                                    ->minLength(8)
                                    ->revealable(),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $user = User::create([
                                    'name' => $data['name'],
                                    'email' => $data['email'],
                                    'password' => Hash::make($data['password']),
                                ]);

                                // Sesuaikan nama role kalau berbeda di seeder kamu.
                                $user->update(['role' => UserRole::PEMOHON]);

                                return $user->getKey();
                            })
                            ->createOptionModalHeading('Buat Akun Pengguna Baru')
                            ->helperText('Cari akun yang sudah terdaftar, atau klik "+" untuk membuat akun baru langsung dari sini.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
