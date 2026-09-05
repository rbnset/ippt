<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RisalahPertimbangan;
use App\Models\User;

class RisalahPertimbanganPolicy
{
    public function view(User $user, RisalahPertimbangan $record): bool { return $user->hasAnyRole([UserRole::ADMIN, UserRole::PEMOHON, UserRole::STAFF, UserRole::KADIS]); }
    public function create(User $user): bool { return $user->hasAnyRole([UserRole::ADMIN, UserRole::STAFF]); }
    public function update(User $user, RisalahPertimbangan $record): bool { return $user->hasAnyRole([UserRole::ADMIN, UserRole::STAFF]); }
    public function delete(User $user, RisalahPertimbangan $record): bool { return $user->isAdmin(); }
}
