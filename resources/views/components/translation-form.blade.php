@php
    $locales = $model->translationLocales();
    $defaultLocale = $model->translationDefaultLocale();
    $translatableSlugs = $model->getTranslatableFieldSlugs();
    $fieldSlugs = collect($translatableSlugs)->values()->all();
    $fieldMeta = $model->fieldsCollection()
        ->filter(fn ($field) => isset($field['slug']) && in_array($field['slug'], $translatableSlugs, true))
        ->mapWithKeys(fn ($field) => [
            $field['slug'] => [
                'name' => __($field['name'] ?? $field['slug']),
                'type' => $field['type'] ?? '',
            ],
        ])
        ->all();
    $resourceId = ($model->exists ?? false) ? $model->getKey() : null;
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
        aiEndpoint: @js(route('aura.translations.ai.translate')),
        csrf: document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content'),
        defaultLocale: @js($defaultLocale),
        fieldSlugs: @js($fieldSlugs),
        fieldMeta: @js($fieldMeta),
        resourceSlug: @js($model->getSlug()),
        resourceId: @js($resourceId),
        ai: {
            open: false,
            loading: false,
            phase: 'idle',
            locale: null,
            error: null,
            fields: [],
            sentPayload: null,
            rawResponse: null,
        },
        copyFromDefault(locale) {
            this.fieldSlugs.forEach((slug) => {
                this.$wire.set(`form.fields.translations.${locale}.values.${slug}`, this.$wire.get(`form.fields.${slug}`));
            });
        },
        buildAiFields(locale) {
            return this.fieldSlugs
                .map((slug) => {
                    const source = this.$wire.get(`form.fields.${slug}`) ?? '';
                    const existing = this.$wire.get(`form.fields.translations.${locale}.values.${slug}`) ?? '';

                    return {
                        slug: slug,
                        label: this.fieldMeta[slug]?.name || slug,
                        source: source,
                        target: existing,
                    };
                })
                .filter((field) => String(field.source).trim() !== '');
        },
        async translateWithAi(locale) {
            const fields = this.buildAiFields(locale);

            this.ai = {
                open: true,
                loading: false,
                phase: 'prepared',
                locale: locale,
                error: null,
                fields: fields,
                sentPayload: null,
                rawResponse: null,
            };

            if (fields.length === 0) {
                this.ai.error = @js(__('Add source text before requesting an AI translation.'));
                this.ai.phase = 'error';

                return;
            }

            const payload = {
                resource_slug: this.resourceSlug,
                resource_id: this.resourceId,
                source_locale: this.defaultLocale,
                target_locale: locale,
                fields: Object.fromEntries(fields.map((field) => [field.slug, field.source])),
                field_meta: this.fieldMeta,
            };

            this.ai.sentPayload = payload;
            this.ai.loading = true;
            this.ai.phase = 'requesting';

            try {
                const response = await fetch(this.aiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await response.json().catch(() => ({}));

                if (! response.ok) {
                    const message = data.message || Object.values(data.errors || {})?.flat()?.[0] || @js(__('AI translation failed.'));
                    throw new Error(message);
                }

                this.ai.sentPayload = data.request_payload || payload;
                this.ai.rawResponse = data.raw_response || data;
                this.ai.fields = fields.map((field) => ({
                    ...field,
                    target: data.translations?.[field.slug] ?? field.target,
                }));
                this.ai.phase = 'review';
            } catch (error) {
                this.ai.error = error.message || @js(__('AI translation failed.'));
                this.ai.phase = 'error';
            } finally {
                this.ai.loading = false;
            }
        },
        approveAiTranslation() {
            this.ai.fields.forEach((field) => {
                this.$wire.set(`form.fields.translations.${this.ai.locale}.values.${field.slug}`, field.target ?? '');
            });

            this.$dispatch('notify', {
                type: 'success',
                message: @js(__('AI translations applied.')),
            });

            this.closeAiTranslation();
        },
        closeAiTranslation() {
            this.ai.open = false;
        },
        formattedAiPayload() {
            return JSON.stringify(this.ai.sentPayload || {}, null, 2);
        },
        formattedAiResponse() {
            return JSON.stringify(this.ai.rawResponse || {}, null, 2);
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

        <div class="relative min-h-10 w-48">
            @foreach ($locales as $locale => $label)
                @if ($locale !== $defaultLocale)
                    <div
                        x-bind:class="activeLocale === @js($locale) ? 'opacity-100' : 'pointer-events-none opacity-0'"
                        class="absolute inset-y-0 right-0 flex items-center justify-end gap-1 transition"
                    >
                        <button type="button" title="{{ __('Copy from default') }}" data-copy-from-default="{{ $locale }}" x-on:click="copyFromDefault(@js($locale))" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-primary-600 hover:bg-primary-50 hover:text-primary-700 dark:hover:bg-primary-900/20">
                            {{ __('Copy') }}
                        </button>

                        <button type="button" title="{{ __('Translate with AI') }}" data-ai-translate-button="{{ $locale }}" x-on:click="translateWithAi(@js($locale))" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-primary-600 hover:bg-primary-50 hover:text-primary-700 dark:hover:bg-primary-900/20">
                            {{ __('AI Translation') }}
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

    <x-aura::dialog x-model="ai.open" data-ai-translation-modal>
        <x-aura::dialog.panel :modalAttributes="['modalClasses' => 'max-w-5xl']">
            <div data-ai-translation-panel>
                <div class="pr-10">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-aura::dialog.title>{{ __('AI Translation') }}</x-aura::dialog.title>
                        <span class="-mt-4 inline-flex items-center rounded-full border border-gray-200 px-2 py-0.5 text-[11px] font-semibold uppercase text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <span x-text="defaultLocale"></span>
                            <span class="px-1 text-gray-300 dark:text-gray-600">/</span>
                            <span x-text="ai.locale"></span>
                        </span>
                    </div>

                    <div class="-mt-1 flex flex-wrap items-center gap-2 text-xs font-medium text-gray-400 dark:text-gray-500">
                        <span class="inline-flex items-center gap-1.5" x-bind:class="['prepared', 'requesting', 'review'].includes(ai.phase) ? 'text-primary-600 dark:text-primary-400' : ''">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            {{ __('Prepared') }}
                        </span>
                        <span class="h-px w-8 bg-gray-200 dark:bg-gray-700"></span>
                        <span class="inline-flex items-center gap-1.5" x-bind:class="['requesting', 'review'].includes(ai.phase) ? 'text-primary-600 dark:text-primary-400' : ''">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            {{ __('Requesting AI') }}
                        </span>
                        <span class="h-px w-8 bg-gray-200 dark:bg-gray-700"></span>
                        <span class="inline-flex items-center gap-1.5" x-bind:class="ai.phase === 'review' ? 'text-primary-600 dark:text-primary-400' : ''">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            {{ __('Review') }}
                        </span>
                    </div>
                </div>

                <div class="-mx-6 mt-5 max-h-[68vh] overflow-y-auto border-y border-gray-100 bg-gray-50/60 px-6 py-5 dark:border-gray-700 dark:bg-gray-900/40">
                <template x-if="ai.error">
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300" data-ai-translation-error x-text="ai.error"></div>
                </template>

                <template x-if="ai.loading">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" data-ai-translation-loading>
                        <x-aura::icon.loading />
                        <span>{{ __('Translating...') }}</span>
                    </div>
                </template>

                <div class="space-y-3">
                    <template x-for="field in ai.fields" x-bind:key="field.slug">
                        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                                <h3 class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400" x-text="field.label"></h3>
                                <span class="text-[11px] font-medium text-gray-400 dark:text-gray-500" x-text="field.slug"></span>
                            </div>

                            <div class="grid gap-0 md:grid-cols-2">
                                <div class="border-b border-gray-100 p-4 md:border-b-0 md:border-r dark:border-gray-800">
                                    <div class="mb-2 text-[11px] font-semibold uppercase text-gray-400 dark:text-gray-500">{{ __('Source') }}</div>
                                    <div class="min-h-24 whitespace-pre-wrap rounded-md bg-gray-50 px-3 py-2.5 text-sm leading-6 text-gray-800 dark:bg-gray-950 dark:text-gray-100" data-ai-source-string x-text="field.source"></div>
                                </div>

                                <label class="block p-4">
                                    <span class="mb-2 block text-[11px] font-semibold uppercase text-gray-400 dark:text-gray-500">{{ __('Translation') }}</span>
                                    <textarea
                                        x-model="field.target"
                                        x-bind:rows="String(field.target || '').length > 140 ? 5 : 3"
                                        data-ai-target-string
                                        x-bind:aria-label="field.label + ' ' + @js(__('translation'))"
                                        class="block min-h-24 w-full resize-y rounded-md border-gray-300 bg-white px-3 py-2.5 text-sm leading-6 text-gray-900 shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-primary-500"
                                    ></textarea>
                                </label>
                            </div>
                        </section>
                    </template>
                </div>

                <details class="mt-4 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900/60">
                    <summary class="cursor-pointer px-4 py-3 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('JSON exchange') }}</summary>
                    <div class="grid gap-3 border-t border-gray-100 p-4 lg:grid-cols-2 dark:border-gray-800">
                        <div>
                            <div class="mb-2 text-[11px] font-semibold uppercase text-gray-400 dark:text-gray-500">{{ __('Sent') }}</div>
                            <pre class="max-h-56 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-3 text-xs leading-5 text-gray-700 dark:bg-gray-950 dark:text-gray-200" data-ai-json-sent x-text="formattedAiPayload()"></pre>
                        </div>

                        <div>
                            <div class="mb-2 text-[11px] font-semibold uppercase text-gray-400 dark:text-gray-500">{{ __('Received') }}</div>
                            <pre class="max-h-56 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-3 text-xs leading-5 text-gray-700 dark:bg-gray-950 dark:text-gray-200" data-ai-json-response x-text="formattedAiResponse()"></pre>
                        </div>
                    </div>
                </details>
            </div>

                <x-aura::dialog.footer>
                    <div class="flex w-full flex-wrap items-center justify-between gap-3">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            <span x-text="ai.fields.length"></span>
                            <span>{{ __('strings ready') }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <x-aura::dialog.close>
                                <x-aura::button.transparent type="button">
                                    {{ __('Cancel') }}
                                </x-aura::button.transparent>
                            </x-aura::dialog.close>

                            <x-aura::button.primary type="button" data-ai-approve-translation x-on:click="approveAiTranslation()" x-bind:disabled="ai.loading || ai.fields.length === 0 || ai.phase !== 'review'">
                                {{ __('Approve') }}
                            </x-aura::button.primary>
                        </div>
                    </div>
                </x-aura::dialog.footer>
            </div>
        </x-aura::dialog.panel>
    </x-aura::dialog>
</div>
