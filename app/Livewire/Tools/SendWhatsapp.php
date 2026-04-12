<?php

namespace App\Livewire\Tools;

use App\Services\Tools\SendWhatsappService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SendWhatsapp extends Component
{
    public string $phone = '6281310307754@s.whatsapp.net';

    public string $message = 'selamat malam bro';

    public string $replyMessageId = '';

    public bool $isForwarded = false;

    public int $duration = 86400;

    public bool $hasConfiguredCredentials = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasConfiguredCredentials = filled(config('tools.whatsapp.username'))
            && filled(config('tools.whatsapp.password'));
    }

    public function send(SendWhatsappService $sendWhatsappService): void
    {
        $this->phone = trim($this->phone);
        $this->message = trim($this->message);
        $this->replyMessageId = trim($this->replyMessageId);

        $this->validate([
            'phone' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'replyMessageId' => ['nullable', 'string', 'max:255'],
            'isForwarded' => ['boolean'],
            'duration' => ['required', 'integer', 'min:1', 'max:604800'],
        ]);

        try {
            $this->result = $sendWhatsappService->send(
                phone: $this->phone,
                message: $this->message,
                replyMessageId: $this->replyMessageId,
                isForwarded: $this->isForwarded,
                duration: $this->duration,
            );
            $this->errorMessage = null;
            $this->hasConfiguredCredentials = true;
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
            $this->hasConfiguredCredentials = filled(config('tools.whatsapp.username'))
                && filled(config('tools.whatsapp.password'));
        }
    }

    public function getPrettyResponseProperty(): string
    {
        return json_encode($this->result['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function render(): View
    {
        return view('livewire.tools.send-whatsapp');
    }
}
