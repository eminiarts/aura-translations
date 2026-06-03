<?php

namespace Aura\Translations\Support;

use Illuminate\Support\Facades\App;

class Locales
{
    public static function all(): array
    {
        return config('aura-translations.locales', []);
    }

    public static function codes(): array
    {
        return array_keys(static::all());
    }

    public static function current(): string
    {
        return App::currentLocale();
    }

    public static function default(): string
    {
        $default = config('aura-translations.default_locale', config('app.locale', 'en'));

        return array_key_exists($default, static::all()) ? $default : array_key_first(static::all());
    }

    public static function fallback(): string
    {
        return config('aura-translations.fallback_locale', static::default());
    }

    public static function label(string $locale): string
    {
        return static::all()[$locale] ?? strtoupper($locale);
    }
}
