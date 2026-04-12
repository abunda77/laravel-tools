<?php

namespace App\Livewire\Tools;

use App\Services\Tools\SendWhatsappService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SendWhatsapp extends Component
{
    /** @var array<int, array{id: string, display_name: string, state: string, jid: string, created_at: string}> */
    public array $devices = [];

    public string $deviceId = '';

    public string $phone = '6281310307754@s.whatsapp.net';

    public string $message = 'selamat malam bro';

    public string $replyMessageId = '';

    public bool $isForwarded = false;

    public int $duration = 86400;

    public bool $hasConfiguredCredentials = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(SendWhatsappService $sendWhatsappService): void
    {
        $this->hasConfiguredCredentials = filled(config('tools.whatsapp.username'))
            && filled(config('tools.whatsapp.password'));

        if ($this->hasConfiguredCredentials) {
            $this->loadDevices($sendWhatsappService);
        }
    }

    public function send(SendWhatsappService $sendWhatsappService): void
    {
        $this->phone = trim($this->phone);
        $this->message = trim($this->message);
        $this->replyMessageId = trim($this->replyMessageId);

        $this->validate([
            'deviceId' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'replyMessageId' => ['nullable', 'string', 'max:255'],
            'isForwarded' => ['boolean'],
            'duration' => ['required', 'integer', 'min:1', 'max:604800'],
        ]);

        try {
            $this->result = $sendWhatsappService->send(
                deviceId: $this->deviceId,
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

    public function refreshDevices(SendWhatsappService $sendWhatsappService): void
    {
        $this->loadDevices($sendWhatsappService);
    }

    private function loadDevices(SendWhatsappService $sendWhatsappService): void
    {
        try {
            $this->devices = $sendWhatsappService->devices();
            $this->deviceId = $this->devices[0]['id'] ?? '';
            $this->errorMessage = null;

            if (empty($this->devices)) {
                $this->errorMessage = 'Daftar device WhatsApp kosong. Pastikan device sudah tersedia di provider.';
            }
        } catch (\Throwable $throwable) {
            $this->devices = [];
            $this->deviceId = '';
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.tools.send-whatsapp');
    }
}
