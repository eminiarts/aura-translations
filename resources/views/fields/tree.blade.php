@foreach ($fields as $key => $field)
    @checkCondition($model, $field, $form)
        @if (($field['type'] ?? null) === 'Aura\\Base\\Fields\\Panel' && isset($field['fields']) && is_iterable($field['fields']))
            @include('aura-translations::fields.panel', [
                'field' => $field,
                'fieldKey' => $key,
                'locales' => $locales,
                'defaultLocale' => $defaultLocale,
                'translatableSlugs' => $translatableSlugs,
            ])
        @elseif (isset($field['slug']) && in_array($field['slug'], $translatableSlugs, true))
            @include('aura-translations::fields.localized', [
                'field' => $field,
                'fieldKey' => $key,
                'locales' => $locales,
                'defaultLocale' => $defaultLocale,
                'translatableSlugs' => $translatableSlugs,
            ])
        @else
            <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode" wire:key="resource-field-{{ $key }}" />
        @endif
    @endcheckCondition
@endforeach
