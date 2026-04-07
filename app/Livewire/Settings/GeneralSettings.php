<?php

namespace App\Livewire\Settings;

use App\Support\Settings\SystemSettings;
use Livewire\Component;

class GeneralSettings extends Component
{
    public int $requestTimeoutSeconds = 30;

    public int $requestRetryTimes = 1;

    public int $requestRetrySleepMs = 500;

    public string $queueConnection = 'database';

    public function mount(SystemSettings $settings): void
    {
        $this->requestTimeoutSeconds = (int) $settings->get('request_timeout_seconds');
        $this->requestRetryTimes     = (int) $settings->get('request_retry_times');
        $this->requestRetrySleepMs   = (int) $settings->get('request_retry_sleep_ms');
        $this->queueConnection       = (string) $settings->get('queue_connection');
    }

    public function save(SystemSettings $settings): void
    {
        $validated = $this->validate([
            'requestTimeoutSeconds' => ['required', 'integer', 'min:1', 'max:300'],
            'requestRetryTimes'     => ['required', 'integer', 'min:0', 'max:10'],
            'requestRetrySleepMs'   => ['required', 'integer', 'min:0', 'max:5000'],
            'queueConnection'       => ['required', 'string', 'in:sync,database'],
        ]);

        $settings->putMany([
            'request_timeout_seconds' => $validated['requestTimeoutSeconds'],
            'request_retry_times'     => $validated['requestRetryTimes'],
            'request_retry_sleep_ms'  => $validated['requestRetrySleepMs'],
            'queue_connection'        => $validated['queueConnection'],
        ]);

        session()->flash('status', 'Settings saved successfully.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings.general-settings');
    }
}
