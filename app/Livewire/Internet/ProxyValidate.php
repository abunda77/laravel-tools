<?php

namespace App\Livewire\Internet;

use App\Services\Internet\ProxyValidateService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Livewire\Component;

class ProxyValidate extends Component
{
    public string $selectedSource = 'All Proxies';

    public string $filterAddress = '';

    public string $filterProtocol = 'All';

    public string $filterCountry = 'All';

    public string $filterAnonymity = 'All';

    public string $filterStatus = 'All';

    /**
     * @var list<string>
     */
    public array $sourceOptions = [];

    /**
     * @var list<string>
     */
    public array $selectedAddresses = [];

    /**
     * @var list<array{
     *     address: string,
     *     host: string,
     *     port: int,
     *     protocol: string,
     *     country: string,
     *     anonymity: string,
     *     status: string,
     *     response_time_ms: int|null,
     *     detected_ip: string|null,
     *     error_message: string|null,
     *     last_checked_at: string|null
     * }>
     */
    public array $proxies = [];

    public ?string $errorMessage = null;

    public bool $hasLoaded = false;

    public bool $isValidating = false;

    public int $validationProcessed = 0;

    public int $validationTotal = 0;

    public string $validationStatus = 'Idle';

    public function mount(ProxyValidateService $proxyValidateService): void
    {
        $this->sourceOptions = $proxyValidateService->sourceOptions();
    }

    public function fetchProxies(ProxyValidateService $proxyValidateService): void
    {
        $this->validate([
            'selectedSource' => ['required', 'string', 'in:'.implode(',', $this->sourceOptions)],
        ]);

        try {
            $this->proxies = $proxyValidateService->fetch($this->selectedSource);
            $this->errorMessage = null;
            $this->selectedAddresses = [];
        } catch (\Throwable $throwable) {
            $this->proxies = [];
            $this->errorMessage = $throwable->getMessage();
            $this->selectedAddresses = [];
        }

        $this->hasLoaded = true;
    }

    public function validateProxy(string $address, ProxyValidateService $proxyValidateService): void
    {
        $this->runValidation([$address], $proxyValidateService);
    }

    public function validateSelected(ProxyValidateService $proxyValidateService): void
    {
        if ($this->selectedAddresses === []) {
            return;
        }

        $this->runValidation($this->selectedAddresses, $proxyValidateService);
    }

    public function selectVisibleValidOnly(): void
    {
        $this->selectedAddresses = $this->addressesFromProxies(
            array_filter($this->filteredProxies, static fn (array $proxy): bool => $proxy['status'] === 'Valid')
        );
    }

    public function selectVisibleUncheckedOnly(): void
    {
        $this->selectedAddresses = $this->addressesFromProxies(
            array_filter($this->filteredProxies, static fn (array $proxy): bool => $proxy['status'] === 'Unchecked')
        );
    }

    public function exportSelectedCsv(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['address', 'protocol', 'country', 'anonymity', 'status', 'response_time_ms', 'detected_ip', 'last_checked_at']);

            foreach ($this->selectedProxies() as $proxy) {
                fputcsv($handle, [
                    $proxy['address'],
                    $proxy['protocol'],
                    $proxy['country'],
                    $proxy['anonymity'],
                    $proxy['status'],
                    $proxy['response_time_ms'],
                    $proxy['detected_ip'],
                    $proxy['last_checked_at'],
                ]);
            }

            fclose($handle);
        }, 'proxy-validate-export.csv');
    }

    public function exportSelectedTxt(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            foreach ($this->selectedProxies() as $proxy) {
                echo implode(' | ', [
                    $proxy['address'],
                    $proxy['protocol'],
                    $proxy['country'],
                    $proxy['anonymity'],
                    $proxy['status'],
                    $proxy['response_time_ms'] ?? '-',
                    $proxy['detected_ip'] ?? '-',
                ]).PHP_EOL;
            }
        }, 'proxy-validate-export.txt');
    }

    public function getProxyCountProperty(): int
    {
        return count($this->proxies);
    }

    public function getFilteredProxiesProperty(): array
    {
        return array_values(array_filter($this->proxies, function (array $proxy): bool {
            if ($this->filterAddress !== '' && ! str_contains(strtolower($proxy['address']), strtolower($this->filterAddress))) {
                return false;
            }

            if ($this->filterProtocol !== 'All' && $proxy['protocol'] !== $this->filterProtocol) {
                return false;
            }

            if ($this->filterCountry !== 'All' && $proxy['country'] !== $this->filterCountry) {
                return false;
            }

            if ($this->filterAnonymity !== 'All' && $proxy['anonymity'] !== $this->filterAnonymity) {
                return false;
            }

            if ($this->filterStatus !== 'All' && $proxy['status'] !== $this->filterStatus) {
                return false;
            }

            return true;
        }));
    }

    public function getFilteredProxyCountProperty(): int
    {
        return count($this->filteredProxies);
    }

    public function getCountryOptionsProperty(): array
    {
        return $this->extractDistinctValues('country');
    }

    public function getProtocolOptionsProperty(): array
    {
        return $this->extractDistinctValues('protocol');
    }

    public function getAnonymityOptionsProperty(): array
    {
        return $this->extractDistinctValues('anonymity');
    }

    public function getStatusOptionsProperty(): array
    {
        return ['All', 'Unchecked', 'Valid', 'Invalid'];
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selectedAddresses);
    }

    public function getAllFilteredSelectedProperty(): bool
    {
        $filteredAddresses = $this->filteredAddresses();

        if ($filteredAddresses === []) {
            return false;
        }

        return count(array_diff($filteredAddresses, $this->selectedAddresses)) === 0;
    }

    public function toggleSelectAllFiltered(): void
    {
        $filteredAddresses = $this->filteredAddresses();

        if ($filteredAddresses === []) {
            return;
        }

        if ($this->allFilteredSelected) {
            $this->selectedAddresses = array_values(array_diff($this->selectedAddresses, $filteredAddresses));

            return;
        }

        $this->selectedAddresses = array_values(array_unique([
            ...$this->selectedAddresses,
            ...$filteredAddresses,
        ]));
    }

    public function statusPillClass(string $status): string
    {
        return match ($status) {
            'Valid' => 'status-pill--ready',
            'Invalid' => 'status-pill--danger',
            default => 'status-pill--pending',
        };
    }

    public function progressPercentage(): int
    {
        if ($this->validationTotal === 0) {
            return 0;
        }

        return (int) round(($this->validationProcessed / $this->validationTotal) * 100);
    }

    public function progressMarkup(): string
    {
        $barClasses = $this->isValidating
            ? 'bg-gradient-to-r from-emerald-500 via-sky-500 to-emerald-500 bg-[length:200%_100%] animate-pulse'
            : 'bg-emerald-500';

        return sprintf(
            '<div class="space-y-2"><div class="flex items-center justify-between text-xs text-[rgb(var(--app-muted))]"><span>%s</span><span>%d / %d</span></div><div class="h-2.5 overflow-hidden rounded-full bg-[rgba(var(--app-surface-strong),0.8)]"><div class="h-full rounded-full transition-all duration-300 %s" style="width: %d%%;"></div></div></div>',
            e($this->validationStatus),
            $this->validationProcessed,
            $this->validationTotal,
            $barClasses,
            $this->progressPercentage(),
        );
    }

    private function findProxyIndex(string $address): ?int
    {
        foreach ($this->proxies as $index => $proxy) {
            if ($proxy['address'] === $address) {
                return $index;
            }
        }

        return null;
    }

    private function extractDistinctValues(string $key): array
    {
        $values = array_values(array_unique(array_map(
            static fn (array $proxy): string => $proxy[$key],
            $this->proxies
        )));

        sort($values);

        return ['All', ...$values];
    }

    /**
     * @param  list<string>  $addresses
     */
    private function runValidation(array $addresses, ProxyValidateService $proxyValidateService): void
    {
        $validAddresses = array_values(array_filter($addresses, fn (string $address): bool => $this->findProxyIndex($address) !== null));

        if ($validAddresses === []) {
            return;
        }

        $this->isValidating = true;
        $this->validationProcessed = 0;
        $this->validationTotal = count($validAddresses);
        $this->validationStatus = 'Starting validation';
        $this->stream(to: 'validation-progress', content: $this->progressMarkup(), replace: true);

        foreach ($validAddresses as $position => $address) {
            $index = $this->findProxyIndex($address);

            if ($index === null) {
                continue;
            }

            $this->validationStatus = "Checking {$address}";
            $this->stream(to: 'validation-progress', content: $this->progressMarkup(), replace: true);

            $this->proxies[$index] = $proxyValidateService->validate($this->proxies[$index]);
            $this->validationProcessed = $position + 1;
            $this->validationStatus = "Processed {$this->validationProcessed} of {$this->validationTotal}";
            $this->stream(to: 'validation-progress', content: $this->progressMarkup(), replace: true);
        }

        $this->isValidating = false;
        $this->validationStatus = 'Validation finished';
        $this->stream(to: 'validation-progress', content: $this->progressMarkup(), replace: true);
    }

    /**
     * @return list<string>
     */
    private function filteredAddresses(): array
    {
        return array_values(array_map(
            static fn (array $proxy): string => $proxy['address'],
            $this->filteredProxies
        ));
    }

    /**
     * @param  iterable<array{
     *     address: string,
     *     host: string,
     *     port: int,
     *     protocol: string,
     *     country: string,
     *     anonymity: string,
     *     status: string,
     *     response_time_ms: int|null,
     *     detected_ip: string|null,
     *     error_message: string|null,
     *     last_checked_at: string|null
     * }>  $proxies
     * @return list<string>
     */
    private function addressesFromProxies(iterable $proxies): array
    {
        $addresses = [];

        foreach ($proxies as $proxy) {
            $addresses[] = $proxy['address'];
        }

        return array_values($addresses);
    }

    /**
     * @return list<array{
     *     address: string,
     *     host: string,
     *     port: int,
     *     protocol: string,
     *     country: string,
     *     anonymity: string,
     *     status: string,
     *     response_time_ms: int|null,
     *     detected_ip: string|null,
     *     error_message: string|null,
     *     last_checked_at: string|null
     * }>
     */
    private function selectedProxies(): array
    {
        return array_values(array_filter(
            $this->proxies,
            fn (array $proxy): bool => in_array($proxy['address'], $this->selectedAddresses, true)
        ));
    }

    public function render()
    {
        return view('livewire.internet.proxy-validate');
    }
}
