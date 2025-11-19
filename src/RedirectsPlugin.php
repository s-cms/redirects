<?php

namespace SmartCms\Redirects;

use Filament\Contracts\Plugin;
use Filament\Panel;
use SmartCms\Redirects\Filament\Resources\Redirects\RedirectResource;

class RedirectsPlugin implements Plugin
{
    public static ?string $navigationGroup = null;

    public function getId(): string
    {
        return 'redirects';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            RedirectResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(?string $navigationGroup = null): static
    {
        return app(static::class, ['navigationGroup' => $navigationGroup]);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
