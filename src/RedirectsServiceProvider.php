<?php

namespace SmartCms\Redirects;

use Illuminate\Routing\Router;
use SmartCms\Redirects\Http\Middlewares\RedirectMiddleware;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RedirectsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'redirects';

    public static string $viewNamespace = 'redirects';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasMigration('create_redirects_table')
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('smartcms/redirects');
            });
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        $this->app->booted(function () {
            $router = $this->app->make(Router::class);
            $router->pushMiddlewareToGroup('web', RedirectMiddleware::class);
        });
    }
}
