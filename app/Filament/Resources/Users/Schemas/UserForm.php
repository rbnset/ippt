<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Pengguna')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')->label('Nama')->required()->maxLength(255)->autofocus(),
                            TextInput::make('email')->label('Email')->email()->required()->maxLength(150)->unique(ignoreRecord: true),
                            TextInput::make('password')
                                ->label('Password')->password()->revealable()
                                ->dehydrated(fn (?string $state): bool => filled($state))
                                ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->rules([Password::defaults()]),
                            Select::make('role')->label('Role')->options(UserRole::class)->enum(UserRole::class)->required()->native(false)->searchable()->preload(),
                            Select::make('status_akun')
                                ->label('Status Akun')
                                ->options(['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'])
                                ->default('aktif')->required()->native(false),
                        ]),
                    ]),
                Section::make('Foto Profil')
                    ->description('Foto yang digunakan pada area akun dan profil pemohon.')
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Foto Profil')->image()->avatar()->disk('public')->directory('avatars')
                            ->maxSize(2048)->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('JPG/PNG/WebP, maksimal 2 MB.'),
                    ]),
            ]);
    }
}
