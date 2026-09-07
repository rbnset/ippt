<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\KeputusanIppt;
use App\Models\User;

class KeputusanIpptPolicy
{
    public function view(User $user, KeputusanIppt $record): bool { return $user->hasAnyRole([UserRole::ADMIN, UserRole::PEMOHON, UserRole::STAFF, UserRole::TIM_TEKNIS, UserRole::KADIS]); }
    public function create(User $user): bool { return $user->hasRole(UserRole::STAFF); }
    public function update(User $user, KeputusanIppt $record): bool { return $user->hasRole(UserRole::STAFF) && in_array($record->status?->value, ['draf','ditolak'], true); }
    public function delete(User $user, KeputusanIppt $record): bool { return $user->isAdmin() && in_array($record->status?->value, ['draf','ditolak'], true); }
}
