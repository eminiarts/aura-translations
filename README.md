# Aura Translations

Aura Translations adds multilingual content editing to Aura CMS resources. It stores translated field values in a separate `aura_translations` table and replaces the default Aura create/edit forms with locale-aware forms.

## Installation

Require the package:

```bash
composer require eminiarts/aura-translations
```

If the package is not available on Packagist yet, add the GitHub repository to your application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/eminiarts/aura-translations"
        }
    ]
}
```

To install the package into Aura's plugin folder instead of `vendor/`, add Composer's installers extender to your application and configure the installer path:

```json
{
    "require": {
        "oomphinc/composer-installers-extender": "^2.0",
        "eminiarts/aura-translations": "^0.1.0"
    },
    "config": {
        "allow-plugins": {
            "oomphinc/composer-installers-extender": true
        }
    },
    "extra": {
        "installer-types": [
            "aura-plugin"
        ],
        "installer-paths": {
            "plugins/aura/translations": [
                "eminiarts/aura-translations"
            ]
        }
    }
}
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag=aura-translations-migrations
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=aura-translations-config
```

## Configure Locales

Locales are configured in `config/aura-translations.php`:

```php
'locales' => [
    'en' => 'English',
    'de' => 'Deutsch',
],

'default_locale' => env('AURA_TRANSLATIONS_DEFAULT_LOCALE', config('app.locale', 'en')),
'fallback_locale' => env('AURA_TRANSLATIONS_FALLBACK_LOCALE', config('app.fallback_locale', 'en')),
```

The default locale uses the resource's normal fields. Other locales are stored as translation records.

## Make a Resource Translatable

Add the `HasTranslations` trait to the Aura resource and declare the field slugs that should be translated:

```php
namespace App\Aura\Resources;

use Aura\Base\Resource;
use Aura\Translations\Concerns\HasTranslations;

class Movie extends Resource
{
    use HasTranslations;

    public static array $translatable = [
        'title',
        'overview',
        'slug',
    ];
}
```

You can also mark fields directly in `getFields()`:

```php
[
    'name' => 'Title',
    'slug' => 'title',
    'type' => 'Aura\\Base\\Fields\\Text',
    'validation' => 'required|max:255',
    'translatable' => true,
]
```

## Admin Editing

When a resource uses `HasTranslations`, Aura uses translation-aware create/edit views. The form shows:

- A compact language switcher for configured locales.
- A translation status control for non-default locales.
- A copy action to copy default-locale field values into the active translation.
- A subtle translation indicator on fields that are multilingual.

Only fields listed in `static $translatable` or marked with `'translatable' => true` receive translated inputs.

## Validation

Default-locale validation stays on the regular fields. Translation validation is generated for every non-default locale.

If a source field is required, its translation becomes required only when that translation is published:

```php
'title' => 'required|max:255',
```

becomes:

```php
'form.fields.translations.de.values.title' => 'required_if:form.fields.translations.de.status,published|max:255'
```

Override translation-specific validation per field with `translation_validation`:

```php
[
    'name' => 'SEO Title',
    'slug' => 'seo_title',
    'type' => 'Aura\\Base\\Fields\\Text',
    'translatable' => true,
    'translation_validation' => 'nullable|max:70',
]
```

## Reading Translated Values

Use `translateField()` when rendering content:

```php
$movie->translateField('title', 'de');
$movie->translateField('overview', 'de', fallback: true);
```

If the requested locale is the default locale, the normal resource field is returned. If fallback is enabled and the translation is empty, the default value is returned.

For translated slugs:

```php
$movie->translatedSlug('de', fallback: true);
```

## Querying Translations

Only return resources with a published translation in a locale:

```php
Movie::translatedIn('de')->get();
```

Find a resource by translated slug:

```php
Movie::whereTranslatedSlug('mein-film', 'de')->first();
```

For the default locale, `whereTranslatedSlug()` falls back to the resource's normal `slug` column.

## Translation Statuses

Statuses are configured in `config/aura-translations.php`:

```php
'statuses' => [
    'draft' => 'Draft',
    'needs_review' => 'Needs review',
    'published' => 'Published',
],

'default_status' => 'draft',
'published_status' => 'published',
```

Published translations receive a `published_at` timestamp. Draft translations clear `published_at`.

## Storage

Translations are stored in `aura_translations`:

- `translatable_type` / `translatable_id`: the translated resource.
- `locale`: locale code, such as `de`.
- `status`: workflow status.
- `values`: JSON object containing only translatable field slugs.
- `source_locale`: the default locale used as the source.
- `published_at`: set when the translation status is published.

Non-translatable fields are ignored when translation payloads are saved.
