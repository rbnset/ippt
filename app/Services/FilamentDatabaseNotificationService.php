<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Writes Filament database notifications directly to the notifications table.
 *
 * This intentionally bypasses Laravel's queue so the in-app notification bell
 * works on shared/cloud hosting without a queue worker.
 */
class FilamentDatabaseNotificationService
{
    public function send(User $user, Notification $notification): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => Notification::class,
            'data' => $notification->getDatabaseMessage(),
        ]);
    }
}
