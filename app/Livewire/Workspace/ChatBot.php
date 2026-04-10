<?php

namespace App\Livewire\Workspace;

use App\Models\ChatAttachment;
use App\Models\ChatSession;
use App\Models\LlmModel;
use App\Models\User;
use App\Services\Ai\ChatResponder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class ChatBot extends Component
{
    use WithFileUploads;

    public string $provider = 'openai';

    public ?int $modelId = null;

    public ?int $activeSessionId = null;

    public string $prompt = '';

    public bool $webSearch = true;

    /** @var array<int, mixed> */
    public array $uploads = [];

    public function mount(): void
    {
        $this->modelId = LlmModel::query()
            ->active()
            ->where('provider', $this->provider)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->value('id');
    }

    public function updatedProvider(): void
    {
        $this->modelId = LlmModel::query()
            ->active()
            ->where('provider', $this->provider)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->value('id');
    }

    public function selectSession(int $sessionId): void
    {
        $session = $this->ownedSessions()->findOrFail($sessionId);

        $this->activeSessionId = $session->id;
        $this->provider = $session->provider;
        $this->modelId = $session->llm_model_id;
    }

    public function startNewSession(): void
    {
        $this->activeSessionId = null;
        $this->prompt = '';
        $this->uploads = [];
    }

    public function deleteSession(int $sessionId): void
    {
        $session = $this->ownedSessions()
            ->with('messages.attachments')
            ->findOrFail($sessionId);

        $session->messages
            ->flatMap(fn ($message) => $message->attachments)
            ->each(fn (ChatAttachment $attachment) => Storage::disk($attachment->disk)->delete($attachment->path));

        $session->delete();

        if ($this->activeSessionId === $sessionId) {
            $this->startNewSession();
        }

        session()->flash('chatbot_status', 'Sesi chat berhasil dihapus.');
    }

    public function send(ChatResponder $responder): void
    {
        $validated = $this->validate([
            'provider' => ['required', 'string', 'in:openai,gemini,anthropic,perplexity'],
            'modelId' => ['required', 'integer', 'exists:llm_models,id'],
            'prompt' => ['required', 'string', 'max:12000'],
            'webSearch' => ['boolean'],
            'uploads.*' => ['file', 'max:12288', 'mimes:jpg,jpeg,png,webp,pdf,txt,md,csv,json,doc,docx'],
        ]);

        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $model = LlmModel::query()
            ->active()
            ->where('provider', $validated['provider'])
            ->findOrFail($validated['modelId']);

        $session = $this->sessionFor($user, $model);

        $userMessage = $session->messages()->create([
            'role' => 'user',
            'content' => trim($validated['prompt']),
            'provider' => $model->provider,
            'model_name' => $model->name,
        ]);

        foreach ($this->uploads as $upload) {
            $path = $upload->store('chatbot-attachments');
            $mimeType = $upload->getMimeType();

            $userMessage->attachments()->create([
                'disk' => 'local',
                'path' => $path,
                'original_name' => $upload->getClientOriginalName(),
                'mime_type' => $mimeType,
                'size' => $upload->getSize() ?: 0,
                'type' => Str::startsWith((string) $mimeType, 'image/') ? 'image' : 'document',
            ]);
        }

        try {
            $response = $responder->respond($user, $session, $model, $userMessage->load('attachments'), (bool) $validated['webSearch']);
        } catch (Throwable $exception) {
            report($exception);

            session()->flash('chatbot_error', $exception->getMessage());

            return;
        }

        $assistantMessage = $session->messages()->create([
            'role' => 'assistant',
            'content' => $response->content,
            'provider' => $model->provider,
            'model_name' => $model->name,
            'metadata' => ['usage' => $response->usage],
        ]);

        foreach (array_values($response->citations) as $index => $citation) {
            $assistantMessage->citations()->create([
                'title' => $citation['title'] ?? null,
                'url' => $citation['url'],
                'snippet' => $citation['snippet'] ?? null,
                'source_provider' => $citation['source_provider'] ?? $model->provider,
                'position' => $index + 1,
            ]);
        }

        if ($session->title === 'New chat') {
            $session->update(['title' => Str::limit(trim($validated['prompt']), 60)]);
        }

        $session->touch();

        $this->activeSessionId = $session->id;
        $this->prompt = '';
        $this->uploads = [];
    }

    public function render(): View
    {
        return view('livewire.workspace.chat-bot', [
            'sessions' => $this->ownedSessions()->latest('updated_at')->get(),
            'messages' => $this->activeSessionId
                ? $this->ownedSessions()
                    ->with(['messages.attachments', 'messages.citations'])
                    ->find($this->activeSessionId)
                    ?->messages()
                    ->with(['attachments', 'citations'])
                    ->oldest()
                    ->get() ?? collect()
                : collect(),
            'modelsByProvider' => LlmModel::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get()
                ->groupBy('provider'),
        ]);
    }

    private function sessionFor(User $user, LlmModel $model): ChatSession
    {
        if ($this->activeSessionId !== null) {
            return $this->ownedSessions()->findOrFail($this->activeSessionId);
        }

        return ChatSession::query()->create([
            'user_id' => $user->id,
            'title' => 'New chat',
            'provider' => $model->provider,
            'llm_model_id' => $model->id,
            'model_name' => $model->name,
        ]);
    }

    private function ownedSessions()
    {
        return ChatSession::query()->where('user_id', auth()->id());
    }
}
