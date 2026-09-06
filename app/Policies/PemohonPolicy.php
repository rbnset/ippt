<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Pemohon;
use App\Models\User;

class PemohonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([UserRole::ADMIN, UserRole::PEMOHON, UserRole::STAFF]);
    }

    public function view(User $user, Pemohon $record): bool
    {
        if ($user->hasAnyRole([UserRole::ADMIN, UserRole::STAFF])) {
            return true;
        }

        return $user->hasRole(UserRole::PEMOHON) && $record->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::ADMIN, UserRole::STAFF]);
    }

    public function update(User $user, Pemohon $record): bool
    {
        return $user->hasAnyRole([UserRole::ADMIN, UserRole::STAFF]);
    }

    public function delete(User $user, Pemohon $record): bool { return $user->isAdmin(); }
    public function deleteAny(User $user): bool { return $user->isAdmin(); }
    public function restore(User $user, Pemohon $record): bool { return $user->isAdmin(); }
    public function restoreAny(User $user): bool { return $user->isAdmin(); }
    public function forceDelete(User $user, Pemohon $record): bool { return $user->isAdmin(); }
    public function forceDeleteAny(User $user): bool { return $user->isAdmin(); }
    public function replicate(User $user, Pemohon $record): bool { return $user->isAdmin(); }
    public function reorder(User $user): bool { return $user->isAdmin(); }
}
