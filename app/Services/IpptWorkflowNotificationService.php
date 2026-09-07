<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Permohonan;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class IpptWorkflowNotificationService
{
    public function __construct(
        private readonly FilamentDatabaseNotificationService $databaseNotifications,
    ) {
    }

    public function pemohon(Permohonan $permohonan, string $title, string $body, string $level = 'info'): void
    {
        $user = $permohonan->loadMissing('pemohon.user')->pemohon?->user;
        if ($user) {
            $this->send($user, $title, $body, $level);
        }
    }

    public function roles(array $roles, string $title, string $body, string $level = 'info'): void
    {
        $users = \App\Models\User::query()->whereIn('role', array_map(fn ($role) => $role instanceof UserRole ? $role->value : $role, $roles))->get();
        foreach ($users as $user) {
            $this->send($user, $title, $body, $level);
        }
    }

    private function send($user, string $title, string $body, string $level): void
    {
        $notification = Notification::make()->title($title)->body($body);
        match ($level) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            default => $notification->info(),
        };
        $this->databaseNotifications->send($user, $notification);
    }
}
