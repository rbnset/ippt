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
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Pemohon $record): bool
    {
        return $this->viewAny($user);
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
