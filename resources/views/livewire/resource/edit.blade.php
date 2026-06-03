<div autocomplete="off">
    @section('title', __('Edit :resource', ['resource' => __($model->singularName())]))

    {{ app('aura')::injectView('post_edit_breadcrumbs_before') }}

    @if (! $inModal)
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
            @if (Route::has('aura.' . $model->getSlug() . '.index'))
                <x-aura::breadcrumbs.li :href="route('aura.' . $model->getSlug() . '.index')" :title="__($model->getPluralName())" />
            @else
                <x-aura::breadcrumbs.li :title="__($model->getPluralName())" />
            @endif
            <x-aura::breadcrumbs.li :title="$model->title()" />
        </x-aura::breadcrumbs>
    @endif

    {{ app('aura')::injectView('post_edit_breadcrumbs_after') }}

    @include($model->editHeaderView())

    <div class="grid gap-6 mt-4 aura-edit-post-container sm:grid-cols-3" x-data="{ model: @entangle('form') }">
        <div class="col-span-1 mx-0 sm:col-span-3">
            @include('aura-translations::components.translation-form', [
                'fields' => $this->editFields,
            ])

            <x-aura::validation-errors />
        </div>
    </div>

    @if ($inModal)
        <div class="flex justify-end mt-4 space-x-2">
            <x-aura::dialog.close>
                <x-aura::button.transparent>{{ __('Cancel') }}</x-aura::button.transparent>
            </x-aura::dialog.close>
            <x-aura::button onclick="Livewire.dispatchTo('aura::resource-edit', 'saveModel')" wire:loading.attr="disabled">
                <div wire:loading.delay wire:target="save">
                    <x-aura::icon.loading />
                </div>
                {{ __('Save') }}
            </x-aura::button>
        </div>
    @endif
</div>
