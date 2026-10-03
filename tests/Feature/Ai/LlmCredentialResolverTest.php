<?php

namespace Tests\Feature\Ai;

use App\Models\ApiKey;
use App\Services\Ai\LlmCredentialResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LlmCredentialResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_openrouter_key_resolves_from_database(): void
    {
        ApiKey::query()->create([
            'name' => 'openrouter',
            'label' => 'OpenRouter',
            'value' => 'sk-openrouter-secret',
            'is_active' => true,
        ]);

        $resolver = app(LlmCredentialResolver::class);

        $this->assertSame('sk-openrouter-secret', $resolver->key('openrouter'));
        $this->assertSame('sk-openrouter-secret', $resolver->requireKey('openrouter'));

        config(['ai.providers.openrouter.key' => null]);
        $resolver->configureLaravelAiProvider('openrouter');

        $this->assertSame('sk-openrouter-secret', config('ai.providers.openrouter.key'));
    }

    public function test_openrouter_key_throws_when_missing(): void
    {
        $resolver = app(LlmCredentialResolver::class);

        $this->expectExceptionMessage('API key untuk provider OPENROUTER');
        $resolver->requireKey('openrouter');
    }
}
