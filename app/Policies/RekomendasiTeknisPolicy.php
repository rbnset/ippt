<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RekomendasiTeknis;
use App\Models\User;

class RekomendasiTeknisPolicy
{
    public function view(User $user, RekomendasiTeknis $record): bool { return $user->hasAnyRole([UserRole::ADMIN, UserRole::PEMOHON, UserRole::TIM_TEKNIS, UserRole::KABID, UserRole::KADIS]); }
    public function create(User $user): bool { return $user->hasAnyRole([UserRole::ADMIN, UserRole::TIM_TEKNIS]); }
    public function update(User $user, RekomendasiTeknis $record): bool { return $user->hasAnyRole([UserRole::ADMIN, UserRole::TIM_TEKNIS]) && in_array($record->status?->value, ['draf','ditolak'], true); }
    public function delete(User $user, RekomendasiTeknis $record): bool { return $user->isAdmin() && in_array($record->status?->value, ['draf','ditolak'], true); }
}
