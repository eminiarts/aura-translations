@php
    $fieldPath = optional($field)['slug'];
    $editorId = 'translation-editor-' . Str::slug($fieldPath);
    $value = data_get($this->form['fields'] ?? [], $fieldPath, '');
@endphp

<x-aura::fields.wrapper :field="$field" wire:ignore>
    <div @if($field['disabled'] ?? false) class="rounded-md bg-gray-100 opacity-50 cursor-not-allowed" @endif>
        <div @if($field['disabled'] ?? false) class="pointer-events-none" @endif>
            <div
                id="{{ $editorId }}"
                data-translation-field="{{ $fieldPath }}"
                x-data="{
                    disabled: @json($field['disabled'] ?? false),
                    init() {
                        let quill = new window.Quill(this.$refs.quill, {
                            theme: 'snow',
                            readOnly: this.disabled
                        });

                        quill.on('text-change', () => {
                            $wire.$set('form.fields.{{ $fieldPath }}', quill.root.innerHTML);
                        });
                    }
                }"
                x-ref="quill"
            >{!! $value !!}</div>
        </div>
    </div>
</x-aura::fields.wrapper>

@assets
    @vite(['resources/js/quill.js'], 'vendor/aura/libs')
@endassets
