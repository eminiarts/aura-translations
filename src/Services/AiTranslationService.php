<?php

namespace Aura\Translations\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AiTranslationService
{
    public function translate(string $sourceLocale, string $targetLocale, array $fields, array $fieldMeta = []): array
    {
        $fields = $this->normalizeFields($fields);

        if ($fields === []) {
            return [
                'provider' => config('aura-translations.ai.provider', 'openai_compatible'),
                'model' => config('aura-translations.ai.model'),
                'translations' => [],
                'raw_response' => ['translations' => [], 'notes' => ''],
            ];
        }

        if (! config('aura-translations.ai.enabled', true)) {
            throw new RuntimeException(__('AI translation is disabled.'));
        }

        $apiKey = config('aura-translations.ai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException(__('No AI translation API key is configured.'));
        }

        $requestPayload = $this->buildRequestPayload($sourceLocale, $targetLocale, $fields, $fieldMeta);

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('aura-translations.ai.timeout', 45))
                ->post(config('aura-translations.ai.endpoint'), $requestPayload)
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException(__('AI translation request failed: :message', [
                'message' => $exception->getMessage(),
            ]), previous: $exception);
        }

        $decoded = $this->decodeResponse($response);
        $translations = Arr::only($decoded['translations'] ?? [], array_keys($fields));

        return [
            'provider' => config('aura-translations.ai.provider', 'openai_compatible'),
            'model' => config('aura-translations.ai.model'),
            'translations' => collect($translations)
                ->map(fn ($value) => is_scalar($value) ? (string) $value : '')
                ->all(),
            'raw_response' => $decoded,
        ];
    }

    protected function normalizeFields(array $fields): array
    {
        return collect($fields)
            ->filter(fn ($value) => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    protected function buildRequestPayload(string $sourceLocale, string $targetLocale, array $fields, array $fieldMeta): array
    {
        return [
            'model' => config('aura-translations.ai.model'),
            'temperature' => (float) config('aura-translations.ai.temperature', 0.2),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt($sourceLocale, $targetLocale),
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'source_locale' => $sourceLocale,
                        'target_locale' => $targetLocale,
                        'fields' => $fields,
                        'field_meta' => Arr::only($fieldMeta, array_keys($fields)),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'aura_translation_response',
                    'strict' => true,
                    'schema' => $this->responseSchema(array_keys($fields)),
                ],
            ],
        ];
    }

    protected function systemPrompt(string $sourceLocale, string $targetLocale): string
    {
        return implode(' ', [
            "Translate CMS field values from {$sourceLocale} to {$targetLocale}.",
            'Return only JSON that matches the provided schema.',
            'Preserve field keys, HTML tags, Markdown, URLs, entities, placeholders, variables, and line breaks.',
            'For slug-like fields, return a URL-safe lowercase target-language slug.',
            'Do not add fields that were not provided.',
        ]);
    }

    protected function responseSchema(array $fieldSlugs): array
    {
        $fieldProperties = collect($fieldSlugs)
            ->mapWithKeys(fn (string $slug) => [$slug => ['type' => 'string']])
            ->all();

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'translations' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => $fieldProperties,
                    'required' => $fieldSlugs,
                ],
                'notes' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['translations', 'notes'],
        ];
    }

    protected function decodeResponse(?array $response): array
    {
        $refusal = data_get($response, 'choices.0.message.refusal');

        if (filled($refusal)) {
            throw new RuntimeException((string) $refusal);
        }

        $content = data_get($response, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException(__('AI translation response was empty.'));
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException(__('AI translation response was not valid JSON.'), previous: $exception);
        }

        if (! is_array($decoded) || ! is_array($decoded['translations'] ?? null)) {
            throw new RuntimeException(__('AI translation response did not include translations.'));
        }

        return $decoded;
    }
}
