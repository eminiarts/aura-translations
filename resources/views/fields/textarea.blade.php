<x-aura::fields.wrapper :field="$field">
    <textarea
        wire:model="form.fields.{{ optional($field)['slug'] }}"
        error="form.fields.{{ optional($field)['slug'] }}"
        data-translation-field="{{ optional($field)['slug'] }}"
        rows="{{ $field['rows'] ?? '4' }}"
        name="post_fields_{{ Str::slug(optional($field)['slug']) }}"
        id="resource-field-{{ optional($field)['slug'] }}"
        class="block w-full rounded-md border-gray-500/30 p-3 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-primary-500 dark:focus:ring-opacity-50"
        autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"
    ></textarea>
</x-aura::fields.wrapper>
