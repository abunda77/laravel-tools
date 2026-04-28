<?php

namespace App\Livewire\Tools;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class WallMeter extends Component
{
    public float $horizontalDistance = 3.0;

    public float $elevationAngleDeg = 40.0;

    public float $instrumentHeight = 1.2;

    public function mount(): void
    {
        $this->recalculate();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['horizontalDistance', 'elevationAngleDeg', 'instrumentHeight'], true)) {
            $this->recalculate();
        }
    }

    public function recalculate(): void
    {
        $this->validate([
            'horizontalDistance' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'elevationAngleDeg' => ['required', 'numeric', 'min:1', 'max:89'],
            'instrumentHeight' => ['required', 'numeric', 'gt:0', 'max:200'],
        ]);
    }

    public function getVerticalComponentProperty(): float
    {
        return round($this->horizontalDistance * tan(deg2rad($this->elevationAngleDeg)), 4);
    }

    public function getTotalHeightProperty(): float
    {
        return round($this->instrumentHeight + $this->verticalComponent, 4);
    }

    public function getSightLineLengthProperty(): float
    {
        return round($this->horizontalDistance / cos(deg2rad($this->elevationAngleDeg)), 4);
    }

    public function getTangentValueProperty(): float
    {
        return round(tan(deg2rad($this->elevationAngleDeg)), 6);
    }

    public function getAngleRadProperty(): float
    {
        return round(deg2rad($this->elevationAngleDeg), 6);
    }

    public function render(): View
    {
        return view('livewire.tools.wall-meter');
    }
}
