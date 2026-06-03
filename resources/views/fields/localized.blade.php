@foreach ($locales as $locale => $label)
    <div x-show="activeLocale === @js($locale)" x-cloak class="contents" data-translation-locale-pane>
        @if ($locale === $defaultLocale)
            <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode" wire:key="source-field-{{ $field['slug'] }}" />
        @else
            @php
                $translatedField = $field;
                $translatedField['source_slug'] = $field['slug'];
                $translatedField['slug'] = 'translations.' . $locale . '.values.' . $field['slug'];

                if (isset($field['based_on']) && in_array($field['based_on'], $translatableSlugs, true)) {
                    $translatedField['based_on'] = 'translations.' . $locale . '.values.' . $field['based_on'];
                }
            @endphp

            @include('aura-translations::fields.render', [
                'field' => $translatedField,
                'locale' => $locale,
            ])
        @endif
    </div>
@endforeach
