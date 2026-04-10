<?php

namespace App\Services\Ai;

use App\Models\ApiKey;
use RuntimeException;

class LlmCredentialResolver
{
    /**
     * @var array<string, string>
     */
    private const API_KEY_NAMES = [
        'openai' => 'openai',
        'gemini' => 'gemini',
        'anthropic' => 'anthropic',
        'perplexity' => 'perplexity',
    ];

    public function requireKey(string $provider): string
    {
        $key = $this->key($provider);

        if (blank($key)) {
            throw new RuntimeException('API key untuk provider '.strtoupper($provider).' belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.(self::API_KEY_NAMES[$provider] ?? $provider).'".');
        }

        return $key;
    }

    public function key(string $provider): ?string
    {
        $name = self::API_KEY_NAMES[$provider] ?? $provider;

        $databaseKey = ApiKey::query()
            ->active()
            ->where('name', $name)
            ->first()
            ?->value;

        if (filled($databaseKey)) {
            return $databaseKey;
        }

        return match ($provider) {
            'openai' => config('ai.providers.openai.key'),
            'gemini' => config('ai.providers.gemini.key'),
            'anthropic' => config('ai.providers.anthropic.key'),
            'perplexity' => config('services.perplexity.key'),
            default => null,
        };
    }

    public function configureLaravelAiProvider(string $provider): void
    {
        if (! in_array($provider, ['openai', 'gemini', 'anthropic'], true)) {
            return;
        }

        config(["ai.providers.{$provider}.key" => $this->requireKey($provider)]);
    }
}
