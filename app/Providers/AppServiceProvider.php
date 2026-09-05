<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::disk('private')->buildTemporaryUrlsUsing(
            function (string $path, \DateTimeInterface $expiration, array $options) {
                return URL::temporarySignedRoute(
                    'private-files.show',
                    $expiration,
                    array_merge($options, ['path' => $path]),
                );
            }
        );
    }
}
