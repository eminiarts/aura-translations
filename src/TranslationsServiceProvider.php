<?php

namespace Aura\Translations;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TranslationsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('aura-translations')
            ->hasConfigFile('aura-translations')
            ->hasViews('aura-translations')
            ->hasMigration('create_aura_translations_table');
    }

    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/aura-translations.php' => config_path('aura-translations.php'),
            ], 'aura-translations-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_aura_translations_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_aura_translations_table.php'),
            ], 'aura-translations-migrations');
        }
    }
}
