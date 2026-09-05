<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Permohonan;
use App\Models\User;

class PermohonanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::ADMIN,
            UserRole::PEMOHON,
            UserRole::STAFF,
            UserRole::TIM_TEKNIS,
            UserRole::KABID,
            UserRole::KADIS,
        ]);
    }

    public function view(User $user, Permohonan $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::ADMIN,
            UserRole::PEMOHON,
            UserRole::STAFF,
        ]);
    }

    public function update(User $user, Permohonan $record): bool
    {
        return $user->hasAnyRole([
            UserRole::ADMIN,
            UserRole::PEMOHON,
            UserRole::STAFF,
            UserRole::TIM_TEKNIS,
        ]);
    }

    public function delete(User $user, Permohonan $record): bool { return $user->isAdmin(); }
    public function deleteAny(User $user): bool { return $user->isAdmin(); }
    public function restore(User $user, Permohonan $record): bool { return $user->isAdmin(); }
    public function restoreAny(User $user): bool { return $user->isAdmin(); }
    public function forceDelete(User $user, Permohonan $record): bool { return $user->isAdmin(); }
    public function forceDeleteAny(User $user): bool { return $user->isAdmin(); }
    public function replicate(User $user, Permohonan $record): bool { return $user->isAdmin(); }
    public function reorder(User $user): bool { return $user->isAdmin(); }
}
