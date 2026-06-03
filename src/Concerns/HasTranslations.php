<?php

namespace Aura\Translations\Concerns;

use Aura\Translations\Models\Translation;
use Aura\Translations\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HasTranslations
{
    protected array $pendingAuraTranslations = [];

    public function initializeHasTranslations(): void
    {
        $this->mergeFillable(['translations']);
    }

    public static function bootHasTranslations(): void
    {
        static::saved(function ($model) {
            if (! empty($model->pendingAuraTranslations)) {
                $model->persistAuraTranslations($model->pendingAuraTranslations);
                $model->pendingAuraTranslations = [];
            }
        });
    }

    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();
        $fields = $attributes['fields'] ?? [];

        if ($fields instanceof Collection) {
            $fields = $fields->toArray();
        }

        if (! is_array($fields)) {
            $fields = [];
        }

        $fields['translations'] = $this->translationFormPayload();
        $attributes['fields'] = $fields;

        return $attributes;
    }

    public function auraTranslations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public static function translatable(): array
    {
        return property_exists(static::class, 'translatable') ? static::$translatable : [];
    }

    public function getTranslatableFieldSlugs(): array
    {
        $configured = collect(static::translatable())
            ->filter()
            ->values();

        $fieldConfigured = $this->fieldsCollection()
            ->filter(fn ($field) => (bool) ($field['translatable'] ?? false))
            ->pluck('slug');

        return $configured
            ->merge($fieldConfigured)
            ->unique()
            ->values()
            ->all();
    }

    public function hasTranslatableFields(): bool
    {
        return count($this->getTranslatableFieldSlugs()) > 0;
    }

    public function isTranslatableField(array $field): bool
    {
        return in_array($field['slug'] ?? null, $this->getTranslatableFieldSlugs(), true);
    }

    public function translationLocales(): array
    {
        return Locales::all();
    }

    public function translationDefaultLocale(): string
    {
        return Locales::default();
    }

    public function translationFor(?string $locale = null): ?Translation
    {
        $locale ??= Locales::current();

        return $this->auraTranslations
            ->firstWhere('locale', $locale)
            ?? $this->auraTranslations()->where('locale', $locale)->first();
    }

    public function translateField(string $slug, ?string $locale = null, bool $fallback = false): mixed
    {
        $locale ??= Locales::current();

        if (! in_array($slug, $this->getTranslatableFieldSlugs(), true)) {
            return $this->{$slug};
        }

        if ($locale === $this->translationDefaultLocale()) {
            return $this->{$slug};
        }

        $translation = $this->translationFor($locale);
        $value = data_get($translation?->values ?? [], $slug);

        if (($value === null || $value === '') && $fallback) {
            return $this->translateField($slug, $this->translationDefaultLocale(), false);
        }

        return $value;
    }

    public function translatedSlug(?string $locale = null, bool $fallback = false): ?string
    {
        return $this->translateField('slug', $locale, $fallback);
    }

    public function hasPublishedTranslation(?string $locale = null): bool
    {
        $locale ??= Locales::current();

        if ($locale === $this->translationDefaultLocale()) {
            return true;
        }

        $translation = $this->translationFor($locale);

        if (! $translation) {
            return false;
        }

        return $translation->status === config('aura-translations.published_status', 'published')
            && collect($translation->values ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
    }

    public function scopeTranslatedIn(Builder $query, ?string $locale = null): Builder
    {
        $instance = new static;
        $locale ??= Locales::current();

        if ($locale === $instance->translationDefaultLocale()) {
            return $query;
        }

        return $query->whereHas('auraTranslations', function (Builder $query) use ($locale) {
            $query
                ->where('locale', $locale)
                ->where('status', config('aura-translations.published_status', 'published'));
        });
    }

    public function scopeWhereTranslatedSlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        $instance = new static;
        $locale ??= Locales::current();

        if ($locale === $instance->translationDefaultLocale()) {
            return $query->where('slug', $slug);
        }

        return $query->whereHas('auraTranslations', function (Builder $query) use ($locale, $slug) {
            $query
                ->where('locale', $locale)
                ->where('status', config('aura-translations.published_status', 'published'))
                ->where('values->slug', $slug);
        });
    }

    public function createView()
    {
        return 'aura-translations::livewire.resource.create';
    }

    public function editView()
    {
        return 'aura-translations::livewire.resource.edit';
    }

    public function setTranslationsField($value): void
    {
        $this->pendingAuraTranslations = is_array($value) ? $value : [];
    }

    public function setTranslationsAttribute($value): void
    {
        $this->setTranslationsField($value);
    }

    public function modifyValidationRules(array $rules, array $form): array
    {
        foreach ($this->translationLocales() as $locale => $label) {
            if ($locale === $this->translationDefaultLocale()) {
                continue;
            }

            $rules['form.fields.translations.'.$locale.'.status'] = 'nullable|string';

            foreach ($this->getTranslatableFieldSlugs() as $slug) {
                $field = $this->fieldBySlug($slug);
                $validation = $field['translation_validation']
                    ?? $this->translationValidationForField($field, 'form.fields.translations.'.$locale.'.status');

                $rules['form.fields.translations.'.$locale.'.values.'.$slug] = $validation;
            }
        }

        return $rules;
    }

    protected function translationValidationForField(?array $field, string $statusAttribute): string|array
    {
        $validation = $field['validation'] ?? 'nullable';

        if ($validation === '') {
            return 'nullable';
        }

        if (! is_string($validation)) {
            return $validation;
        }

        $rules = collect(explode('|', $validation))
            ->filter()
            ->reject(fn (string $rule) => Str::startsWith($rule, 'required'))
            ->values();

        $rules->prepend(Str::contains($validation, 'required')
            ? 'required_if:'.$statusAttribute.','.config('aura-translations.published_status', 'published')
            : 'nullable');

        return $rules->implode('|');
    }

    protected function translationFormPayload(): array
    {
        $payload = [];
        $translations = $this->auraTranslations()->get()->keyBy('locale');

        foreach ($this->translationLocales() as $locale => $label) {
            if ($locale === $this->translationDefaultLocale()) {
                continue;
            }

            $translation = $translations->get($locale);

            $payload[$locale] = [
                'status' => $translation?->status ?? config('aura-translations.default_status', 'draft'),
                'values' => Arr::only($translation?->values ?? [], $this->getTranslatableFieldSlugs()),
            ];
        }

        return $payload;
    }

    protected function persistAuraTranslations(array $translations): void
    {
        foreach ($translations as $locale => $payload) {
            if ($locale === $this->translationDefaultLocale()) {
                continue;
            }

            if (! in_array($locale, array_keys($this->translationLocales()), true)) {
                continue;
            }

            $values = Arr::only($payload['values'] ?? [], $this->getTranslatableFieldSlugs());
            $status = $payload['status'] ?? config('aura-translations.default_status', 'draft');
            $existingTranslation = $this->auraTranslations()->where('locale', $locale)->first();
            $publishedAt = $status === config('aura-translations.published_status', 'published')
                ? ($existingTranslation?->published_at ?? now())
                : null;

            $this->auraTranslations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'status' => $status,
                    'values' => $values,
                    'source_locale' => $this->translationDefaultLocale(),
                    'published_at' => $publishedAt,
                ]
            );
        }
    }
}
