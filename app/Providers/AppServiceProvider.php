<?php

namespace App\Providers;

use Cloudinary;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Cloudinary::class, function () {
            $config = config('services.cloudinary', []);

            Cloudinary::config(array_filter([
                'cloud_name' => $config['cloud_name'] ?? null,
                'api_key' => $config['api_key'] ?? null,
                'api_secret' => $config['api_secret'] ?? null,
                'secure' => $config['secure'] ?? true,
                'timeout' => $config['timeout'] ?? 120,
                'connection_timeout' => $config['connection_timeout'] ?? 10,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''));

            return new Cloudinary;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
