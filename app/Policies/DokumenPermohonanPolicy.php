<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StatusDokumen;
use App\Enums\UserRole;
use App\Models\DokumenPermohonan;
use App\Models\User;

class DokumenPermohonanPolicy
{
    public function view(User $user, DokumenPermohonan $record): bool
    {
        return $user->hasAnyRole([UserRole::ADMIN, UserRole::PEMOHON, UserRole::STAFF, UserRole::TIM_TEKNIS, UserRole::KABID, UserRole::KADIS]);
    }

    public function update(User $user, DokumenPermohonan $record): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->hasRole(UserRole::PEMOHON)) return $record->permohonan?->pemohon?->user_id === $user->id && $record->status === StatusDokumen::Ditolak;
        return $user->hasRole(UserRole::STAFF) && $record->status === StatusDokumen::Menunggu;
    }

    public function delete(User $user, DokumenPermohonan $record): bool { return $user->isAdmin(); }
}
