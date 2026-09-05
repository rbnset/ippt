<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PemeriksaanLapangan;
use App\Models\User;

class PemeriksaanLapanganPolicy
{
    public function view(User $user, PemeriksaanLapangan $record): bool { return $user->hasAnyRole([UserRole::ADMIN, UserRole::PEMOHON, UserRole::TIM_TEKNIS, UserRole::KABID]); }
    public function create(User $user): bool { return $user->hasAnyRole([UserRole::ADMIN, UserRole::TIM_TEKNIS]); }
    public function update(User $user, PemeriksaanLapangan $record): bool { return $user->hasAnyRole([UserRole::ADMIN, UserRole::TIM_TEKNIS]) && ! $record->isFinal(); }
    public function delete(User $user, PemeriksaanLapangan $record): bool { return $user->isAdmin() && ! $record->isFinal(); }
}
