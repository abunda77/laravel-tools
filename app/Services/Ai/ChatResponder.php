<?php

namespace App\Services\Ai;

use App\Ai\Agents\ChatBotAgent;
use App\Models\ChatAttachment;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\LlmModel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files;
use Laravel\Ai\Messages\Message;
use RuntimeException;

class ChatResponder
{
    public function __construct(
        private readonly LlmCredentialResolver $credentials,
        private readonly PerplexityClient $perplexity,
    ) {}

    public function respond(User $user, ChatSession $session, LlmModel $model, ChatMessage $userMessage, bool $webSearch): ChatResponse
    {
        $this->ensureSessionBelongsToUser($session, $user);

        if ($model->provider === 'perplexity') {
            return $this->respondWithPerplexity($session, $model, $userMessage);
        }

        return $this->respondWithLaravelAi($session, $model, $userMessage, $webSearch);
    }

    private function respondWithLaravelAi(ChatSession $session, LlmModel $model, ChatMessage $userMessage, bool $webSearch): ChatResponse
    {
        $provider = $this->mapProvider($model->provider);
        $this->credentials->configureLaravelAiProvider($model->provider);

        $sourceContext = [];

        if ($webSearch) {
            $sourceContext = $this->perplexity->search($userMessage->content);
        }

        $response = (new ChatBotAgent($this->conversationMessages($session, beforeMessageId: $userMessage->id)))
            ->prompt(
                prompt: $this->buildPrompt($userMessage->content, $sourceContext),
                attachments: $this->laravelAiAttachments($userMessage->attachments),
                provider: $provider,
                model: $model->name,
                timeout: 120,
            );

        $citations = $this->citationsFromLaravelAi($response->meta->citations)
            ->merge($sourceContext)
            ->unique('url')
            ->values()
            ->all();

        return new ChatResponse(
            content: (string) $response,
            citations: $citations,
            usage: method_exists($response->usage, 'toArray') ? $response->usage->toArray() : [],
        );
    }

    private function respondWithPerplexity(ChatSession $session, LlmModel $model, ChatMessage $userMessage): ChatResponse
    {
        $messages = [
            [
                'role' => 'system',
                'content' => (string) (new ChatBotAgent)->instructions(),
            ],
        ];

        foreach ($this->conversationMessages($session, beforeMessageId: $userMessage->id) as $message) {
            $messages[] = [
                'role' => $message->role->value,
                'content' => (string) $message->content,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $this->buildPerplexityPrompt($userMessage),
        ];

        return $this->perplexity->chat($model->name, $messages);
    }

    /**
     * @return Message[]
     */
    private function conversationMessages(ChatSession $session, int $beforeMessageId): array
    {
        return $session->messages()
            ->where('id', '<', $beforeMessageId)
            ->whereIn('role', ['user', 'assistant'])
            ->oldest()
            ->limit(40)
            ->get()
            ->map(fn (ChatMessage $message): Message => new Message($message->role, $message->content))
            ->all();
    }

    /**
     * @param  Collection<int, ChatAttachment>  $attachments
     * @return array<int, mixed>
     */
    private function laravelAiAttachments(Collection $attachments): array
    {
        return $attachments
            ->map(function (ChatAttachment $attachment): mixed {
                if (Str::startsWith((string) $attachment->mime_type, 'image/')) {
                    return Files\Image::fromStorage($attachment->path, $attachment->disk);
                }

                return Files\Document::fromStorage($attachment->path, $attachment->disk);
            })
            ->all();
    }

    /**
     * @param  array<int, array{title?: string|null, url: string, snippet?: string|null}>  $sources
     */
    private function buildPrompt(string $prompt, array $sources): string
    {
        if ($sources === []) {
            return $prompt;
        }

        $sourceBlock = collect($sources)
            ->values()
            ->map(fn (array $source, int $index): string => sprintf(
                '[%d] %s%s%s',
                $index + 1,
                $source['title'] ?? 'Untitled source',
                filled($source['url'] ?? null) ? ' - '.$source['url'] : '',
                filled($source['snippet'] ?? null) ? "\n".$source['snippet'] : '',
            ))
            ->implode("\n\n");

        return <<<PROMPT
Use the source context below for web-grounded claims. Cite the matching bracket number inline.

Source context:
{$sourceBlock}

User request:
{$prompt}
PROMPT;
    }

    private function buildPerplexityPrompt(ChatMessage $userMessage): string
    {
        $attachmentsText = $this->extractPerplexityAttachmentText($userMessage->attachments);

        if ($attachmentsText === '') {
            return $userMessage->content;
        }

        return $userMessage->content."\n\nAttached text context:\n".$attachmentsText;
    }

    /**
     * @param  Collection<int, ChatAttachment>  $attachments
     */
    private function extractPerplexityAttachmentText(Collection $attachments): string
    {
        return $attachments
            ->map(function (ChatAttachment $attachment): string {
                if (Str::startsWith((string) $attachment->mime_type, 'image/')) {
                    throw new RuntimeException('Perplexity direct API pada fitur ini tidak memproses upload image. Gunakan OpenAI, Gemini, atau Claude untuk analisis image.');
                }

                if (! in_array($attachment->mime_type, ['text/plain', 'text/markdown', 'text/csv', 'application/json'], true)) {
                    throw new RuntimeException('Perplexity direct API pada fitur ini hanya memproses attachment teks. Gunakan OpenAI, Gemini, atau Claude untuk dokumen non-teks.');
                }

                $contents = Storage::disk($attachment->disk)->get($attachment->path);

                return "### {$attachment->original_name}\n".Str::limit($contents, 12000);
            })
            ->filter()
            ->implode("\n\n");
    }

    /**
     * @return Collection<int, array{title: string|null, url: string, snippet: string|null, source_provider: string}>
     */
    private function citationsFromLaravelAi(Collection $citations): Collection
    {
        return $citations
            ->map(fn (mixed $citation): array => [
                'title' => $citation->title ?? null,
                'url' => $citation->url ?? '',
                'snippet' => null,
                'source_provider' => 'laravel-ai',
            ])
            ->filter(fn (array $citation): bool => filled($citation['url']))
            ->values();
    }

    private function mapProvider(string $provider): Lab
    {
        return match ($provider) {
            'openai' => Lab::OpenAI,
            'gemini' => Lab::Gemini,
            'anthropic' => Lab::Anthropic,
            default => throw new RuntimeException('Provider tidak didukung: '.$provider.'.'),
        };
    }

    private function ensureSessionBelongsToUser(ChatSession $session, User $user): void
    {
        if ((int) $session->user_id !== (int) $user->id) {
            throw new RuntimeException('Sesi chat tidak valid untuk user ini.');
        }
    }
}
