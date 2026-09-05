<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Enums\UserRole;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin(); }
    public function view(User $user, User $record): bool { return $user->isAdmin(); }
    public function create(User $user): bool { return $user->isAdmin(); }
    public function update(User $user, User $record): bool { return $user->isAdmin(); }
    public function delete(User $user, User $record): bool { return $user->isAdmin(); }
    public function deleteAny(User $user): bool { return $user->isAdmin(); }
    public function restore(User $user, User $record): bool { return $user->isAdmin(); }
    public function restoreAny(User $user): bool { return $user->isAdmin(); }
    public function forceDelete(User $user, User $record): bool { return $user->isAdmin(); }
    public function forceDeleteAny(User $user): bool { return $user->isAdmin(); }
    public function replicate(User $user, User $record): bool { return $user->isAdmin(); }
    public function reorder(User $user): bool { return $user->isAdmin(); }
}
