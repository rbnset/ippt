<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Cek satu role. Menerima UserRole atau string untuk kemudahan penggunaan.
     */
    public function hasRole(UserRole|string $role): bool
    {
        $role = $role instanceof UserRole ? $role : UserRole::tryFrom($role);

        return $role !== null && $this->role === $role;
    }

    /**
     * Cek salah satu dari beberapa role.
     *
     * @param array<int, UserRole|string> $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Admin mempunyai akses penuh pada panel.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::ADMIN);
    }

    /**
     * Data pemohon yang terhubung dengan akun.
     */
    public function pemohon(): HasMany
    {
        return $this->hasMany(Pemohon::class);
    }

    public function permohonanDiverifikasi(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'diverifikasi_oleh');
    }

    public function permohonanDireviewTeknis(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'direview_teknis_oleh');
    }

    public function permohonanDisetujuiRekomendasi(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'disetujui_rekomendasi_oleh');
    }

    public function permohonanDisetujuiKeputusan(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'disetujui_keputusan_oleh');
    }
}
