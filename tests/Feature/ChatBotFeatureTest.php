<?php

namespace Tests\Feature;

use App\Ai\Agents\ChatBotAgent;
use App\Livewire\Settings\LlmModelManager;
use App\Livewire\Workspace\ChatBot;
use App\Models\ChatSession;
use App\Models\LlmModel;
use App\Models\User;
use App\Services\Ai\ChatResponder;
use App\Services\Ai\ChatResponse;
use App\Services\Ai\PerplexityClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\AiManager;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ChatBotFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_chatbot_page(): void
    {
        $user = User::factory()->create();
        $this->createModel();

        $this->actingAs($user)
            ->get(route('workspace.chatbot'))
            ->assertOk()
            ->assertSeeLivewire(ChatBot::class)
            ->assertSee('Workspace ChatBot');
    }

    public function test_user_can_send_message_and_store_citations(): void
    {
        $user = User::factory()->create();
        $model = $this->createModel();

        $responder = Mockery::mock(ChatResponder::class);
        $responder->shouldReceive('respond')
            ->once()
            ->andReturn(new ChatResponse(
                content: 'Jawaban dengan source [1].',
                citations: [
                    [
                        'title' => 'Laravel AI SDK',
                        'url' => 'https://laravel.com/docs/13.x/ai-sdk',
                        'snippet' => 'Laravel AI SDK documentation.',
                        'source_provider' => 'perplexity',
                    ],
                ],
                usage: ['total_tokens' => 42],
            ));

        $this->app->instance(ChatResponder::class, $responder);

        Livewire::actingAs($user)
            ->test(ChatBot::class)
            ->set('provider', 'openai')
            ->set('modelId', $model->id)
            ->set('prompt', 'Apa itu Laravel AI SDK?')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('prompt', '');

        $this->assertDatabaseHas('chat_sessions', [
            'user_id' => $user->id,
            'provider' => 'openai',
            'model_name' => 'gpt-4.1',
        ]);

        $session = ChatSession::query()->firstOrFail();

        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'role' => 'assistant',
            'content' => 'Jawaban dengan source [1].',
        ]);

        $this->assertDatabaseHas('chat_citations', [
            'title' => 'Laravel AI SDK',
            'url' => 'https://laravel.com/docs/13.x/ai-sdk',
        ]);
    }

    public function test_user_can_delete_chat_session(): void
    {
        $user = User::factory()->create();
        $model = $this->createModel();

        $session = ChatSession::query()->create([
            'user_id' => $user->id,
            'title' => 'Old chat',
            'provider' => 'openai',
            'llm_model_id' => $model->id,
            'model_name' => $model->name,
        ]);

        Livewire::actingAs($user)
            ->test(ChatBot::class)
            ->call('deleteSession', $session->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('chat_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_llm_model_manager_can_create_model(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LlmModelManager::class)
            ->call('openAdd')
            ->set('provider', 'perplexity')
            ->set('name', 'sonar-pro')
            ->set('label', 'Sonar Pro')
            ->set('supportsDocuments', false)
            ->set('supportsImages', false)
            ->set('supportsWebSearch', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('llm_models', [
            'provider' => 'perplexity',
            'name' => 'sonar-pro',
            'label' => 'Sonar Pro',
        ]);
    }

    public function test_send_still_works_when_web_search_fails(): void
    {
        $user = User::factory()->create();
        $model = $this->createModel();

        $this->app->make(AiManager::class)
            ->fakeAgent(ChatBotAgent::class, ['Jawaban tanpa source.']);

        $perplexity = Mockery::mock(PerplexityClient::class);
        $perplexity->shouldReceive('search')
            ->once()
            ->andThrow(new RuntimeException('Perplexity API Error: You exceeded your current quota.'));

        $this->app->instance(PerplexityClient::class, $perplexity);

        Livewire::actingAs($user)
            ->test(ChatBot::class)
            ->set('provider', 'openai')
            ->set('modelId', $model->id)
            ->set('prompt', 'Apa itu Laravel?')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('chat_messages', [
            'role' => 'assistant',
            'content' => 'Jawaban tanpa source.',
        ]);

        $this->assertDatabaseCount('chat_citations', 0);
    }

    public function test_openrouter_provider_appears_in_chatbot(): void
    {
        $user = User::factory()->create();
        $model = $this->createModel(provider: 'openrouter', name: 'openai/gpt-4o', label: 'OpenRouter GPT-4o');

        $this->actingAs($user)
            ->get(route('workspace.chatbot'))
            ->assertSeeLivewire(ChatBot::class)
            ->assertSee('openrouter');

        Livewire::actingAs($user)
            ->test(ChatBot::class)
            ->set('provider', 'openrouter')
            ->assertSet('provider', 'openrouter')
            ->assertSet('modelId', $model->id);
    }

    private function createModel(string $provider = 'openai', string $name = 'gpt-4.1', string $label = 'GPT 4.1'): LlmModel
    {
        return LlmModel::query()->create([
            'provider' => $provider,
            'name' => $name,
            'label' => $label,
            'supports_documents' => true,
            'supports_images' => true,
            'supports_web_search' => true,
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }
}
