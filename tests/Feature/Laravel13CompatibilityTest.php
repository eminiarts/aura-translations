<?php

use Aura\Translations\Models\Translation;
use Aura\Translations\Services\AiTranslationService;
use Aura\Translations\Support\Locales;
use Illuminate\Support\Facades\Schema;

it('boots the package on Laravel 13', function () {
    expect(app()->version())->toStartWith('13.')
        ->and(app(AiTranslationService::class))->toBeInstanceOf(AiTranslationService::class)
        ->and(config('aura-translations.default_status'))->toBe('draft')
        ->and(Locales::codes())->toBe(['en', 'de', 'fr']);
});

it('runs the package migration and persists translations', function () {
    $migration = require dirname(__DIR__, 2).'/database/migrations/create_aura_translations_table.php.stub';
    $migration->up();

    expect(Schema::hasColumns('aura_translations', [
        'translatable_type',
        'translatable_id',
        'locale',
        'status',
        'values',
        'source_locale',
        'published_at',
    ]))->toBeTrue();

    $translation = Translation::query()->forceCreate([
        'translatable_type' => 'App\\Models\\Article',
        'translatable_id' => 1,
        'locale' => 'de',
        'status' => 'published',
        'values' => ['title' => 'Beispiel'],
        'source_locale' => 'en',
    ]);

    expect($translation->fresh()->values)->toBe(['title' => 'Beispiel']);
});

it('does not call the AI provider when there is nothing to translate', function () {
    $result = app(AiTranslationService::class)->translate('en', 'de', [
        'title' => ' ',
        'description' => null,
    ]);

    expect($result['translations'])->toBe([])
        ->and($result['raw_response'])->toBe([
            'translations' => [],
            'notes' => '',
        ]);
});
