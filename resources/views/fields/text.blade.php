<x-aura::fields.wrapper :field="$field">
    @if (optional($field)['live'] === true)
        <x-aura::input.text
            suffix="{{ optional($field)['suffix'] }}"
            prefix="{{ optional($field)['prefix'] }}"
            :disabled="$field['field']->isDisabled($form, $field)"
            wire:model.live="form.fields.{{ optional($field)['slug'] }}"
            error="form.fields.{{ optional($field)['slug'] }}"
            data-translation-field="{{ optional($field)['slug'] }}"
            placeholder="{{ is_array($translated = __(optional($field)['placeholder'] ?? optional($field)['name'])) ? ucfirst(optional($field)['placeholder'] ?? optional($field)['name']) : $translated }}"
            id="resource-field-{{ optional($field)['slug'] }}"
            autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
        />
    @else
        <x-aura::input.text
            suffix="{{ optional($field)['suffix'] }}"
            prefix="{{ optional($field)['prefix'] }}"
            :disabled="$field['field']->isDisabled($form, $field)"
            wire:model="form.fields.{{ optional($field)['slug'] }}"
            error="form.fields.{{ optional($field)['slug'] }}"
            data-translation-field="{{ optional($field)['slug'] }}"
            placeholder="{{ is_array($translated = __(optional($field)['placeholder'] ?? optional($field)['name'])) ? ucfirst(optional($field)['placeholder'] ?? optional($field)['name']) : $translated }}"
            id="resource-field-{{ optional($field)['slug'] }}"
            autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
        />
    @endif
</x-aura::fields.wrapper>
