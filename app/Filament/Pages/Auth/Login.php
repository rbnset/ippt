<?php

declare(strict_types=1);
namespace App\Filament\Pages\Auth;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;
class Login extends BaseLogin {
    /** @var view-string */
    protected string $view = 'filament.pages.auth.login';
    public function getTitle(): string | Htmlable { return 'Masuk — Layanan IPPT Kota Yogyakarta'; }
    public function getHeading(): string | Htmlable | null { return 'Masuk ke layanan IPPT'; }
    public function getSubheading(): string | Htmlable | null { return ''; }
    protected function getEmailFormComponent(): TextInput
    {
        return parent::getEmailFormComponent()
            ->label('Email')
            ->placeholder('nama@contoh.com')
            ->autocomplete('email')
            ->autofocus();
    }
    protected function getPasswordFormComponent(): TextInput
    {
        return parent::getPasswordFormComponent()
            ->label('Kata sandi')
            ->placeholder('Masukkan kata sandi')
            ->autocomplete('current-password')
            ->revealable();
    }
}
