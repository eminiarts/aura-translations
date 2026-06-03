<?php

namespace Aura\Translations\Support;

class FieldPath
{
    public static function translation(string $locale, string $slug): string
    {
        return "translations.{$locale}.values.{$slug}";
    }

    public static function status(string $locale): string
    {
        return "translations.{$locale}.status";
    }
}
