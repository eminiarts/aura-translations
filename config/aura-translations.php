<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | The key is the locale stored in the database. The label is shown in Aura.
    |
    */

    'locales' => [
        'en' => 'English',
        'de' => 'Deutsch',
    ],

    'default_locale' => env('AURA_TRANSLATIONS_DEFAULT_LOCALE', config('app.locale', 'en')),

    'fallback_locale' => env('AURA_TRANSLATIONS_FALLBACK_LOCALE', config('app.fallback_locale', 'en')),

    /*
    |--------------------------------------------------------------------------
    | Frontend routing
    |--------------------------------------------------------------------------
    |
    | Supported strategies for consumers:
    | - prefix: /{locale}/blog/{slug}
    | - subdomain: {locale}.example.com/blog/{slug}
    |
    */

    'route_strategy' => env('AURA_TRANSLATIONS_ROUTE_STRATEGY', 'prefix'),

    'hide_missing_translations' => true,

    /*
    |--------------------------------------------------------------------------
    | Translation workflow
    |--------------------------------------------------------------------------
    */

    'statuses' => [
        'draft' => 'Draft',
        'needs_review' => 'Needs review',
        'published' => 'Published',
    ],

    'default_status' => 'draft',

    'published_status' => 'published',

    /*
    |--------------------------------------------------------------------------
    | AI Translation
    |--------------------------------------------------------------------------
    |
    | The default provider is OpenAI-compatible Chat Completions, so it can also
    | be pointed at other LLM providers that expose the same JSON API shape.
    |
    */

    'ai' => [
        'enabled' => env('AURA_TRANSLATIONS_AI_ENABLED', true),
        'provider' => env('AURA_TRANSLATIONS_AI_PROVIDER', 'openai_compatible'),
        'api_key' => env('AURA_TRANSLATIONS_AI_API_KEY', env('OPENAI_API_KEY')),
        'endpoint' => env('AURA_TRANSLATIONS_AI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        'model' => env('AURA_TRANSLATIONS_AI_MODEL', 'gpt-4.1-mini'),
        'timeout' => env('AURA_TRANSLATIONS_AI_TIMEOUT', 45),
        'temperature' => env('AURA_TRANSLATIONS_AI_TEMPERATURE', 0.2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Types
    |--------------------------------------------------------------------------
    |
    | These field types get first-class translation renderers in the admin UI.
    | Unknown field types fall back to Aura's default component rendering.
    |
    */

    'field_types' => [
        'Aura\\Base\\Fields\\Text',
        'Aura\\Base\\Fields\\Textarea',
        'Aura\\Base\\Fields\\Wysiwyg',
        'Aura\\Base\\Fields\\Slug',
    ],
];
