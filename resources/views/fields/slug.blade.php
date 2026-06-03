@php
    if (! optional($field)['based_on']) {
        throw new Exception("The slug field needs a 'based_on' property to work.");
    }
@endphp

<x-aura::fields.wrapper :field="$field">
    <div
        x-data="{
            value: $wire.entangle('form.fields.{{ optional($field)['slug'] }}'),
            custom: @js(! optional($field)['disabled'] ?? false),

            slugify(value) {
                return value
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .trim()
                    .replace(/[\s\W-]+/g, '_')
                    .replace(/&/g, '_and_')
            },

            slugifyTyping(value) {
                return value
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[\s\W-]+/g, '_')
                    .trim()
                    .replace(/&/g, '_and_')
            },

            toggleCustom() {
                this.custom = ! this.custom;

                if (! this.custom) {
                    const basedOn = document.getElementById('resource-field-{{ optional($field)['based_on'] }}');
                    this.value = this.slugify(basedOn?.value ?? '');
                }
            },

            init() {
                const basedOn = document.getElementById('resource-field-{{ optional($field)['based_on'] }}');

                if (basedOn && this.value != this.slugify(basedOn.value) && basedOn.value != '') {
                    this.custom = true;
                }

                if (! this.custom && basedOn) {
                    this.value = this.slugify(basedOn.value);
                }

                basedOn?.addEventListener('input', (event) => {
                    if (! this.custom) {
                        this.value = this.slugify(event.target.value);
                    }
                });
            }
        }"
        class="flex space-x-4"
    >
        <div class="flex-1">
            <x-aura::input.text
                type="text"
                x-bind:disabled="!custom"
                id="resource-field-{{ optional($field)['slug'] }}"
                data-translation-field="{{ optional($field)['slug'] }}"
                @keyup="value = slugifyTyping($event.target.value)"
                x-model="value"
                placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
            />
        </div>

        @if (optional($field)['custom'])
            <div class="flex flex-col custom-slug">
                <button
                    x-ref="toggle"
                    @click="toggleCustom()"
                    type="button"
                    role="switch"
                    :aria-checked="custom"
                    :class="custom ? 'bg-primary-600 border border-primary-900/50 dark:border-gray-900' : 'bg-gray-300 shadow-inner border border-gray-500/30'"
                    class="relative inline-flex w-14 rounded-full px-0 py-1"
                >
                    <span :class="custom ? 'bg-white translate-x-6' : 'bg-white translate-x-1'" class="h-6 w-6 rounded-full transition" aria-hidden="true"></span>
                </button>
            </div>
        @endif
    </div>
</x-aura::fields.wrapper>
