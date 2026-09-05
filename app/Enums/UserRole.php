<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case ADMIN = 'admin';
    case PEMOHON = 'pemohon';
    case STAFF = 'staff';
    case TIM_TEKNIS = 'tim_teknis';
    case KABID = 'kabid';
    case KADIS = 'kadis';

    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::PEMOHON => 'Pemohon',
            self::STAFF => 'Staff IPPT',
            self::TIM_TEKNIS => 'Tim Teknis',
            self::KABID => 'Kepala Bidang',
            self::KADIS => 'Kepala Dinas',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $options, self $role): array => $options + [$role->value => $role->getLabel()],
            [],
        );
    }
}
