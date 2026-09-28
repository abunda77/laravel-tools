<?php

namespace App\Livewire\Internet;

use DateTime;
use DateTimeZone;
use Livewire\Component;

class WorldClock extends Component
{
    public string $serverTimezone;

    public int $serverTimestamp;

    public int $jakartaTimestamp;

    public array $timezones = [];

    public function mount(): void
    {
        $this->serverTimezone = date_default_timezone_get();
        $this->serverTimestamp = time();

        $jakartaTz = new DateTimeZone('Asia/Jakarta');
        $jakartaDt = new DateTime('now', $jakartaTz);
        $this->jakartaTimestamp = $jakartaDt->getTimestamp();

        $this->timezones = $this->buildTimezones();
    }

    private function buildTimezones(): array
    {
        $list = [
            ['Asia/Jakarta', 'Indonesia', '🇮🇩', 'GMT+7'],
            ['Asia/Tokyo', 'Japan', '🇯🇵', 'GMT+9'],
            ['Asia/Singapore', 'Singapore', '🇸🇬', 'GMT+8'],
            ['Asia/Shanghai', 'China', '🇨🇳', 'GMT+8'],
            ['Asia/Seoul', 'South Korea', '🇰🇷', 'GMT+9'],
            ['Asia/Dubai', 'UAE', '🇦🇪', 'GMT+4'],
            ['Asia/Bangkok', 'Thailand', '🇹🇭', 'GMT+7'],
            ['Asia/Kolkata', 'India', '🇮🇳', 'GMT+5:30'],
            ['Europe/London', 'United Kingdom', '🇬🇧', 'GMT+0'],
            ['Europe/Paris', 'France', '🇫🇷', 'GMT+1'],
            ['Europe/Berlin', 'Germany', '🇩🇪', 'GMT+1'],
            ['Europe/Moscow', 'Russia', '🇷🇺', 'GMT+3'],
            ['Europe/Rome', 'Italy', '🇮🇹', 'GMT+1'],
            ['America/New_York', 'USA (Eastern)', '🇺🇸', 'GMT-5'],
            ['America/Los_Angeles', 'USA (Pacific)', '🇺🇸', 'GMT-8'],
            ['America/Chicago', 'USA (Central)', '🇺🇸', 'GMT-6'],
            ['America/Toronto', 'Canada', '🇨🇦', 'GMT-5'],
            ['America/Mexico_City', 'Mexico', '🇲🇽', 'GMT-6'],
            ['America/Sao_Paulo', 'Brazil', '🇧🇷', 'GMT-3'],
            ['Australia/Sydney', 'Australia (Sydney)', '🇦🇺', 'GMT+11'],
            ['Australia/Perth', 'Australia (Perth)', '🇦🇺', 'GMT+8'],
            ['Pacific/Auckland', 'New Zealand', '🇳🇿', 'GMT+13'],
            ['Africa/Cairo', 'Egypt', '🇪🇬', 'GMT+2'],
            ['Africa/Johannesburg', 'South Africa', '🇿🇦', 'GMT+2'],
            ['Africa/Lagos', 'Nigeria', '🇳🇬', 'GMT+1'],
        ];

        usort($list, function (array $a, array $b): int {
            $tzA = new DateTimeZone($a[0]);
            $tzB = new DateTimeZone($b[0]);
            $now = new DateTime;

            return $tzA->getOffset($now) <=> $tzB->getOffset($now);
        });

        return $list;
    }

    public function render()
    {
        return view('livewire.internet.world-clock');
    }
}
