<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use App\Filament\Pages\Dashboard;
use App\Models\Pemohon;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

class Register extends BaseRegister
{
    protected string $view = 'filament.pages.auth.register';

    public function getTitle(): string { return 'Daftar Akun Pemohon'; }
    public function getHeading(): string { return 'Buat akun pemohon'; }
    public function getSubheading(): string { return ''; }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent()->label('Nama lengkap')->placeholder('Contoh: Budi Santoso')->autocomplete('name')->autofocus()->columnSpanFull(),
            $this->getEmailFormComponent()->label('Email')->placeholder('nama@contoh.com')->autocomplete('email')->inputMode('email')->columnSpanFull(),
            $this->getPasswordFormComponent()->label('Kata sandi')->placeholder('Buat kata sandi minimal 8 karakter')->autocomplete('new-password')->revealable()->minLength(8)->columnSpanFull(),
            $this->getPasswordConfirmationFormComponent()->label('Konfirmasi kata sandi')->placeholder('Ketik ulang kata sandi')->autocomplete('new-password')->revealable()->columnSpanFull(),
        ]);
    }

    protected function mutateFormDataBeforeRegister(#[SensitiveParameter] array $data): array
    {
        $data['role'] = UserRole::PEMOHON;
        $data['status_akun'] = 'aktif';
        return $data;
    }

    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        $user = $this->getUserModel()::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::PEMOHON,
            'status_akun' => 'aktif',
        ]);

        // Setiap akun role PEMOHON langsung memiliki tepat satu record Pemohon.
        // Data detail dilengkapi di Dasbor sebelum dikirim untuk verifikasi.
        Pemohon::create([
            'user_id' => $user->id,
            'jenis_pemohon' => 'perorangan',
            'nama' => $user->name,
            'email' => $user->email,
            'status_verifikasi' => null,
            'versi_data' => 1,
            'kota' => 'Yogyakarta',
        ]);

        return $user;
    }

    protected function getRedirectUrl(): string { return Dashboard::getUrl(); }
}
