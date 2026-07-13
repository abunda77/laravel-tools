<?php

namespace App\Livewire\Tools;

use App\Models\ApiKey;
use App\Services\Internet\HolidayService;
use Livewire\Component;

class Holiday extends Component
{
    public string $year = '';

    public string $checkDate = '';

    public int $upcomingLimit = 10;

    public bool $hasSavedApiKey = false;

    public ?array $holidays = null;

    public ?array $checkedDate = null;

    public ?array $upcoming = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->year = (string) now()->year;
        $this->checkDate = now()->format('Y-m-d');
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', HolidayService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function loadHolidays(HolidayService $holidayService): void
    {
        $this->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
        ], [
            'year.required' => 'Tahun wajib diisi.',
            'year.integer' => 'Tahun harus angka.',
            'year.between' => 'Tahun harus antara 2000 sampai 2100.',
        ]);

        try {
            $this->holidays = $holidayService->listHolidays((int) $this->year);
            $this->errorMessage = null;
            $this->hasSavedApiKey = filled(
                ApiKey::query()
                    ->active()
                    ->where('name', HolidayService::API_KEY_NAME)
                    ->first()
                    ?->value,
            );
        } catch (\Throwable $throwable) {
            $this->holidays = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function verifyDate(HolidayService $holidayService): void
    {
        $this->validate([
            'checkDate' => ['required', 'date_format:Y-m-d'],
        ], [
            'checkDate.required' => 'Tanggal wajib diisi.',
            'checkDate.date_format' => 'Tanggal harus format YYYY-MM-DD, contoh 2025-01-01.',
        ]);

        try {
            $this->checkedDate = $holidayService->checkDate($this->checkDate);
            $this->errorMessage = null;
            $this->hasSavedApiKey = filled(
                ApiKey::query()
                    ->active()
                    ->where('name', HolidayService::API_KEY_NAME)
                    ->first()
                    ?->value,
            );
        } catch (\Throwable $throwable) {
            $this->checkedDate = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function loadUpcomingHolidays(HolidayService $holidayService): void
    {
        $this->validate([
            'upcomingLimit' => ['required', 'integer', 'min:1', 'max:50'],
        ], [
            'upcomingLimit.required' => 'Limit wajib diisi.',
            'upcomingLimit.integer' => 'Limit harus angka.',
            'upcomingLimit.min' => 'Limit minimal 1.',
            'upcomingLimit.max' => 'Limit maksimal 50.',
        ]);

        try {
            $this->upcoming = $holidayService->upcomingHolidays((int) $this->upcomingLimit);
            $this->errorMessage = null;
            $this->hasSavedApiKey = filled(
                ApiKey::query()
                    ->active()
                    ->where('name', HolidayService::API_KEY_NAME)
                    ->first()
                    ?->value,
            );
        } catch (\Throwable $throwable) {
            $this->upcoming = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function getHolidaysJsonProperty(): string
    {
        return json_encode($this->holidays['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function getCheckedDateJsonProperty(): string
    {
        return json_encode($this->checkedDate['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function getUpcomingJsonProperty(): string
    {
        return json_encode($this->upcoming['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function render()
    {
        return view('livewire.tools.holiday');
    }
}
