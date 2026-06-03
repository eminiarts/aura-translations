@php
    $type = $field['type'] ?? null;

    $view = match ($type) {
        'Aura\\Base\\Fields\\Text' => 'aura-translations::fields.text',
        'Aura\\Base\\Fields\\Textarea' => 'aura-translations::fields.textarea',
        'Aura\\Base\\Fields\\Wysiwyg' => 'aura-translations::fields.wysiwyg',
        'Aura\\Base\\Fields\\Slug' => 'aura-translations::fields.slug',
        default => null,
    };
@endphp

@if ($view)
    @include($view, ['field' => $field])
@else
    <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode" />
@endif
