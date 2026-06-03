@php
    $locales = $model->translationLocales();
    $defaultLocale = $model->translationDefaultLocale();
    $translatableSlugs = $model->getTranslatableFieldSlugs();
    $fieldSlugs = collect($translatableSlugs)->values()->all();
    $markTranslatableFields = function ($fields) use (&$markTranslatableFields, $translatableSlugs) {
        return collect($fields)->map(function ($field) use (&$markTranslatableFields, $translatableSlugs) {
            if (isset($field['slug']) && in_array($field['slug'], $translatableSlugs, true)) {
                $field['translatable'] = true;
            }

            if (isset($field['fields']) && is_iterable($field['fields'])) {
                $field['fields'] = $markTranslatableFields($field['fields']);
            }

            return $field;
        })->all();
    };

    $fields = $markTranslatableFields($fields);
@endphp

<style>
    [data-aura-translations] [data-translation-locale-pane]:not([x-cloak]):not([style*="display: none"]) {
        display: contents !important;
    }
</style>

<div
    x-data="{
        activeLocale: @js($defaultLocale),
        copyFromDefault(locale) {
            const fields = @js($fieldSlugs);
            fields.forEach((slug) => {
                this.$wire.set(`form.fields.translations.${locale}.values.${slug}`, this.$wire.get(`form.fields.${slug}`));
            });
        }
    }"
    data-aura-translations
>
    <div class="mb-4 flex flex-wrap items-center justify-end gap-3">
        <div class="relative min-h-10 w-52">
            @foreach ($locales as $locale => $label)
                @if ($locale !== $defaultLocale)
                    <div
                        x-bind:class="activeLocale === @js($locale) ? 'opacity-100' : 'pointer-events-none opacity-0'"
                        class="absolute inset-y-0 left-0 flex items-center gap-2 transition"
                    >
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Status') }}</span>

                        <x-aura::input.select
                            wire:model="form.fields.translations.{{ $locale }}.status"
                            data-translation-status="{{ $locale }}"
                            size="xs"
                            :options="config('aura-translations.statuses', [])"
                            class="min-w-32"
                        />
                    </div>
                @endif
            @endforeach
        </div>

        <div class="relative min-h-10 w-20">
            @foreach ($locales as $locale => $label)
                @if ($locale !== $defaultLocale)
                    <div
                        x-bind:class="activeLocale === @js($locale) ? 'opacity-100' : 'pointer-events-none opacity-0'"
                        class="absolute inset-y-0 right-0 flex items-center justify-end transition"
                    >
                        <button type="button" title="{{ __('Copy from default') }}" data-copy-from-default="{{ $locale }}" x-on:click="copyFromDefault(@js($locale))" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-primary-600 hover:bg-primary-50 hover:text-primary-700 dark:hover:bg-primary-900/20">
                            {{ __('Copy') }}
                        </button>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="flex shrink-0 justify-end">
            <div class="inline-flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center text-gray-400 dark:text-gray-500" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                    </svg>
                </span>

                <div class="inline-flex rounded-md bg-gray-100 p-0.5 dark:bg-gray-800" role="tablist" aria-label="{{ __('Languages') }}">
                @foreach ($locales as $locale => $label)
                    <button
                        type="button"
                        role="tab"
                        data-locale-tab="{{ $locale }}"
                        x-on:click="activeLocale = @js($locale)"
                        x-bind:aria-selected="activeLocale === @js($locale)"
                        title="{{ $label }}"
                        class="inline-flex h-7 min-w-9 items-center justify-center rounded px-2 text-xs font-semibold uppercase tracking-normal transition"
                        x-bind:class="activeLocale === @js($locale)
                            ? 'bg-primary-600 text-white shadow-sm'
                            : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100'"
                    >
                        <span>{{ strtoupper($locale) }}</span>
                    </button>
                @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-start -mx-2" data-translated-resource-form>
        @include('aura-translations::fields.tree', [
            'fields' => $fields,
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'translatableSlugs' => $translatableSlugs,
        ])
    </div>
</div>
