<?php

namespace Aura\Translations\Http\Controllers;

use Aura\Base\Facades\Aura;
use Aura\Translations\Services\AiTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AiTranslationController extends Controller
{
    public function __invoke(Request $request, AiTranslationService $translator): JsonResponse
    {
        $validated = $request->validate([
            'resource_slug' => ['required', 'string'],
            'resource_id' => ['nullable'],
            'source_locale' => ['required', 'string'],
            'target_locale' => ['required', 'string'],
            'fields' => ['required', 'array'],
            'field_meta' => ['nullable', 'array'],
        ]);

        $resource = Aura::findResourceBySlug($validated['resource_slug']);

        abort_if(! $resource, 404);
        abort_unless(method_exists($resource, 'getTranslatableFieldSlugs'), 422, __('This resource is not translatable.'));

        $this->authorizeResourceTranslation($resource, $request);
        $this->validateLocales($resource, $validated['source_locale'], $validated['target_locale']);

        $translatableSlugs = $resource->getTranslatableFieldSlugs();
        $fields = $this->sanitizeFields(Arr::only($validated['fields'], $translatableSlugs));
        $fieldMeta = $this->sanitizeFieldMeta(Arr::only($validated['field_meta'] ?? [], array_keys($fields)));
        $requestPayload = [
            'resource_slug' => $validated['resource_slug'],
            'source_locale' => $validated['source_locale'],
            'target_locale' => $validated['target_locale'],
            'fields' => $fields,
            'field_meta' => $fieldMeta,
        ];

        try {
            $result = $translator->translate(
                $validated['source_locale'],
                $validated['target_locale'],
                $fields,
                $fieldMeta
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'ai' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'request_payload' => $requestPayload,
            ...$result,
        ]);
    }

    protected function authorizeResourceTranslation($resource, Request $request): void
    {
        if ($request->filled('resource_id')) {
            $model = $resource->newQuery()->findOrFail($request->input('resource_id'));
            Gate::authorize('update', $model);

            return;
        }

        Gate::authorize('create', $resource);
    }

    protected function validateLocales($resource, string $sourceLocale, string $targetLocale): void
    {
        $locales = array_keys($resource->translationLocales());

        if (! in_array($sourceLocale, $locales, true)) {
            throw ValidationException::withMessages([
                'source_locale' => __('The selected source locale is not configured.'),
            ]);
        }

        if (! in_array($targetLocale, $locales, true)) {
            throw ValidationException::withMessages([
                'target_locale' => __('The selected target locale is not configured.'),
            ]);
        }

        if ($targetLocale === $resource->translationDefaultLocale()) {
            throw ValidationException::withMessages([
                'target_locale' => __('The target locale must be different from the default locale.'),
            ]);
        }
    }

    protected function sanitizeFields(array $fields): array
    {
        return collect($fields)
            ->filter(fn ($value) => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    protected function sanitizeFieldMeta(array $fieldMeta): array
    {
        return collect($fieldMeta)
            ->map(function ($meta) {
                if (! is_array($meta)) {
                    return [];
                }

                return [
                    'name' => (string) ($meta['name'] ?? ''),
                    'type' => (string) ($meta['type'] ?? ''),
                ];
            })
            ->all();
    }
}
