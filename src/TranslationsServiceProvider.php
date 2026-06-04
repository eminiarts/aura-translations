<?php

namespace Aura\Translations;

use Aura\Translations\Services\AiTranslationService;
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
            ->hasRoutes('web')
            ->hasMigration('create_aura_translations_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AiTranslationService::class);
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
