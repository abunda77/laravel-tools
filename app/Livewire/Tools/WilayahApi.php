<?php

namespace App\Livewire\Tools;

use App\Models\ApiKey;
use App\Services\Tools\WilayahApiService;
use Livewire\Component;

class WilayahApi extends Component
{
    public ?array $provinces = null;

    public ?array $regencies = null;

    public ?array $districts = null;

    public ?array $villages = null;

    public ?string $errorMessage = null;

    public bool $hasSavedApiKey = false;

    public string $provinceCode = '';

    public string $provinceName = '';

    public string $regencyCode = '';

    public string $regencyName = '';

    public string $districtCode = '';

    public string $districtName = '';

    public string $provinceNameFilter = '';

    public string $regencyNameFilter = '';

    public string $districtNameFilter = '';

    public string $villageNameFilter = '';

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', WilayahApiService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function loadProvinces(WilayahApiService $wilayahApiService): void
    {
        $this->resetResults();

        try {
            $this->provinces = $wilayahApiService->listProvinces(
                filled(trim($this->provinceNameFilter)) ? trim($this->provinceNameFilter) : null,
            );
            $this->errorMessage = null;
            $this->hasSavedApiKey = $this->refreshKeyStatus();
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function selectProvince(string $code, string $name): void
    {
        $this->provinceCode = $code;
        $this->provinceName = $name;
        $this->regencyCode = '';
        $this->regencyName = '';
        $this->districtCode = '';
        $this->districtName = '';
        $this->regencies = null;
        $this->districts = null;
        $this->villages = null;
        $this->errorMessage = null;
    }

    public function loadRegencies(WilayahApiService $wilayahApiService): void
    {
        $this->regencies = null;
        $this->districts = null;
        $this->villages = null;

        if (! $this->validateProvinceSelected()) {
            return;
        }

        try {
            $this->regencies = $wilayahApiService->listRegencies(
                $this->provinceCode,
                filled(trim($this->regencyNameFilter)) ? trim($this->regencyNameFilter) : null,
            );
            $this->errorMessage = null;
            $this->hasSavedApiKey = $this->refreshKeyStatus();
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function selectRegency(string $code, string $name): void
    {
        $this->regencyCode = $code;
        $this->regencyName = $name;
        $this->districtCode = '';
        $this->districtName = '';
        $this->districts = null;
        $this->villages = null;
        $this->errorMessage = null;
    }

    public function loadDistricts(WilayahApiService $wilayahApiService): void
    {
        $this->districts = null;
        $this->villages = null;

        if (! $this->validateRegencySelected()) {
            return;
        }

        try {
            $this->districts = $wilayahApiService->listDistricts(
                $this->regencyCode,
                filled(trim($this->districtNameFilter)) ? trim($this->districtNameFilter) : null,
            );
            $this->errorMessage = null;
            $this->hasSavedApiKey = $this->refreshKeyStatus();
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function selectDistrict(string $code, string $name): void
    {
        $this->districtCode = $code;
        $this->districtName = $name;
        $this->villages = null;
        $this->errorMessage = null;
    }

    public function loadVillages(WilayahApiService $wilayahApiService): void
    {
        $this->villages = null;

        if (! $this->validateDistrictSelected()) {
            return;
        }

        try {
            $this->villages = $wilayahApiService->listVillages(
                $this->districtCode,
                filled(trim($this->villageNameFilter)) ? trim($this->villageNameFilter) : null,
            );
            $this->errorMessage = null;
            $this->hasSavedApiKey = $this->refreshKeyStatus();
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function getProvincesJsonProperty(): string
    {
        return json_encode($this->provinces['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function getRegenciesJsonProperty(): string
    {
        return json_encode($this->regencies['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function getDistrictsJsonProperty(): string
    {
        return json_encode($this->districts['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function getVillagesJsonProperty(): string
    {
        return json_encode($this->villages['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function resetResults(): void
    {
        $this->provinces = null;
        $this->regencies = null;
        $this->districts = null;
        $this->villages = null;
        $this->provinceCode = '';
        $this->provinceName = '';
        $this->regencyCode = '';
        $this->regencyName = '';
        $this->districtCode = '';
        $this->districtName = '';
    }

    private function validateProvinceSelected(): bool
    {
        if (blank($this->provinceCode)) {
            $this->errorMessage = 'Pilih provinsi terlebih dahulu.';

            return false;
        }

        return true;
    }

    private function validateRegencySelected(): bool
    {
        if (blank($this->regencyCode)) {
            $this->errorMessage = 'Pilih kabupaten/kota terlebih dahulu.';

            return false;
        }

        return true;
    }

    private function validateDistrictSelected(): bool
    {
        if (blank($this->districtCode)) {
            $this->errorMessage = 'Pilih kecamatan terlebih dahulu.';

            return false;
        }

        return true;
    }

    private function refreshKeyStatus(): bool
    {
        return filled(
            ApiKey::query()
                ->active()
                ->where('name', WilayahApiService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function render()
    {
        return view('livewire.tools.wilayah-api');
    }
}
