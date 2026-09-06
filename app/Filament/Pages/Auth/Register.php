<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

class Register extends BaseRegister
{
    public function getTitle(): string
    {
        return 'Daftar Akun Pemohon';
    }

    public function getHeading(): string
    {
        return 'Buat akun pemohon';
    }

    public function getSubheading(): string
    {
        return 'Buat akun terlebih dahulu. Setelah masuk, lengkapi data pemohon untuk diverifikasi petugas sebelum Anda dapat mengajukan IPPT.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Akun Pengguna')
                ->description('Gunakan email aktif karena digunakan untuk masuk dan menerima informasi proses layanan.')
                ->schema([
                    $this->getNameFormComponent()
                        ->label('Nama pengguna')
                        ->helperText('Nama ini dapat Anda lengkapi kembali pada data pemohon.'),
                    $this->getEmailFormComponent()->label('Email aktif'),
                    $this->getPasswordFormComponent()->label('Kata sandi'),
                    $this->getPasswordConfirmationFormComponent()->label('Konfirmasi kata sandi'),
                ])
                ->columns(2),
        ]);
    }

    protected function mutateFormDataBeforeRegister(#[SensitiveParameter] array $data): array
    {
        $data['role'] = UserRole::PEMOHON;

        return $data;
    }

    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        return $this->getUserModel()::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::PEMOHON,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return route('pemohon.profil');
    }
}
