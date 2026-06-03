<div>
    @section('title', __('Create ' . $model->singularName()))

    @if (! $inModal)
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
            <x-aura::breadcrumbs.li :href="route('aura.' . $model->getSlug() . '.index')" :title="__($model->getPluralName())" />
            <x-aura::breadcrumbs.li title="{{ __('Create :resource', ['resource' => __($model->singularName())]) }}" />
        </x-aura::breadcrumbs>
    @endif

    <div class="flex items-center justify-between {{ $inModal ? 'mb-8' : 'my-8' }}">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('Create :resource', ['resource' => __($model->singularName())]) }}</h1>
        </div>

        @if (! $inModal && $showSaveButton)
            <div class="save-resource">
                <x-aura::button size="lg" wire:click="save">
                    <div wire:loading wire:target="save">
                        <x-aura::icon.loading />
                    </div>
                    {{ __('Save') }}
                </x-aura::button>
            </div>
        @endif
    </div>

    <div class="grid gap-6 aura-edit-post-container sm:grid-cols-3">
        <div class="col-span-1 sm:col-span-3">
            @if (! $inModal)
                <x-aura::validation-errors />
            @endif

            @include('aura-translations::components.translation-form', [
                'fields' => $this->createFields,
            ])

            <x-aura::validation-errors />
        </div>
    </div>

    @if ($inModal)
        <div class="flex justify-end mt-4 space-x-2">
            <x-aura::dialog.close>
                <x-aura::button.transparent>{{ __('Cancel') }}</x-aura::button.transparent>
            </x-aura::dialog.close>
            <x-aura::button wire:click="save" wire:loading.attr="disabled">
                <div wire:loading.delay wire:target="save">
                    <x-aura::icon.loading />
                </div>
                {{ __('Save') }}
            </x-aura::button>
        </div>
    @endif
</div>
