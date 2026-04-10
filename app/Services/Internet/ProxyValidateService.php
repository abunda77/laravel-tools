<?php

namespace App\Services\Internet;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ProxyValidateService
{
    private const VALIDATION_TIMEOUT_SECONDS = 8;

    private const VALIDATION_CONNECT_TIMEOUT_SECONDS = 2;

    private const VALIDATION_CONCURRENCY = 20;

    /**
     * @var array<string, string>
     */
    private const SOURCE_URLS = [
        'All Proxies' => 'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt',
        'HTTP Only' => 'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/http.txt',
        'SOCKS5 Only' => 'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/socks5.txt',
        'Indonesia Only' => 'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/countries/ID.txt',
    ];

    /**
     * @var list<string>
     */
    private const TEST_ENDPOINTS = [
        'https://httpbin.org/ip',
        'https://api.ipify.org?format=json',
        'https://ifconfig.me/all.json',
    ];

    /**
     * @return list<string>
     */
    public function sourceOptions(): array
    {
        return array_keys(self::SOURCE_URLS);
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
    public function fetch(string $sourceName): array
    {
        $url = $this->resolveSourceUrl($sourceName);

        $response = Http::accept('text/plain')
            ->timeout(20)
            ->connectTimeout(5)
            ->get($url)
            ->throw();

        return $this->parseProxyLines(explode("\n", $response->body()));
    }

    /**
     * @param  array{
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
     * }  $proxy
     * @return array{
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
     * }
     */
    public function validate(array $proxy): array
    {
        $proxyOptions = $this->buildProxyOptions($proxy);
        $lastError = null;

        foreach (self::TEST_ENDPOINTS as $endpoint) {
            $startedAt = microtime(true);

            try {
                $response = Http::acceptJson()
                    ->timeout(self::VALIDATION_TIMEOUT_SECONDS)
                    ->connectTimeout(self::VALIDATION_CONNECT_TIMEOUT_SECONDS)
                    ->withOptions(['proxy' => $proxyOptions])
                    ->get($endpoint)
                    ->throw();

                return $this->validProxy($proxy, $response, $startedAt);
            } catch (Throwable $throwable) {
                $lastError = $this->summarizeError($throwable->getMessage());
            }
        }

        return $this->invalidProxy($proxy, $lastError);
    }

    /**
     * @param  list<array{
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
    public function validateMany(array $proxies): array
    {
        if ($proxies === []) {
            return [];
        }

        $pendingIndexes = array_keys($proxies);
        $lastErrors = [];

        foreach (self::TEST_ENDPOINTS as $endpoint) {
            if ($pendingIndexes === []) {
                break;
            }

            $nextPendingIndexes = [];

            foreach (array_chunk($pendingIndexes, self::VALIDATION_CONCURRENCY) as $indexChunk) {
                $startedAt = microtime(true);
                $responses = Http::pool(
                    fn (Pool $pool): array => array_map(
                        fn (int $index) => $pool->as((string) $index)
                            ->acceptJson()
                            ->timeout(self::VALIDATION_TIMEOUT_SECONDS)
                            ->connectTimeout(self::VALIDATION_CONNECT_TIMEOUT_SECONDS)
                            ->withOptions(['proxy' => $this->buildProxyOptions($proxies[$index])])
                            ->get($endpoint),
                        $indexChunk
                    ),
                    self::VALIDATION_CONCURRENCY
                );

                foreach ($indexChunk as $index) {
                    $result = $responses[(string) $index] ?? null;

                    if ($result instanceof Response && $result->successful()) {
                        $proxies[$index] = $this->validProxy($proxies[$index], $result, $startedAt);

                        continue;
                    }

                    $lastErrors[$index] = $this->resultErrorMessage($result);
                    $nextPendingIndexes[] = $index;
                }
            }

            $pendingIndexes = $nextPendingIndexes;
        }

        foreach ($pendingIndexes as $index) {
            $proxies[$index] = $this->invalidProxy($proxies[$index], $lastErrors[$index] ?? null);
        }

        return array_values($proxies);
    }

    private function resolveSourceUrl(string $sourceName): string
    {
        $sourceName = trim($sourceName);

        if (! array_key_exists($sourceName, self::SOURCE_URLS)) {
            throw new \InvalidArgumentException('Sumber proxy tidak didukung.');
        }

        return self::SOURCE_URLS[$sourceName];
    }

    /**
     * @param  list<string>  $lines
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
    private function parseProxyLines(array $lines): array
    {
        $records = [];

        foreach ($lines as $line) {
            $record = $this->parseProxyLine($line);

            if ($record !== null) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * @return array{
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
     * }|null
     */
    private function parseProxyLine(string $line): ?array
    {
        $line = trim($line);

        if ($line === '') {
            return null;
        }

        $parts = array_map(
            static fn (string $part): string => trim($part),
            explode('|', $line)
        );

        if (count($parts) !== 4) {
            return null;
        }

        [$address, $protocol, $country, $anonymity] = $parts;

        if (! str_contains($address, ':')) {
            return null;
        }

        [$host, $portText] = explode(':', $address, 2);
        $host = trim($host);
        $port = (int) trim($portText);
        $protocol = Str::upper(trim($protocol));

        if ($host === '' || $port < 1 || $port > 65535) {
            return null;
        }

        if (! in_array($protocol, ['HTTP', 'SOCKS4', 'SOCKS5'], true)) {
            return null;
        }

        return [
            'address' => "{$host}:{$port}",
            'host' => $host,
            'port' => $port,
            'protocol' => $protocol,
            'country' => Str::upper(trim($country)),
            'anonymity' => Str::title(trim($anonymity)),
            'status' => 'Unchecked',
            'response_time_ms' => null,
            'detected_ip' => null,
            'error_message' => null,
            'last_checked_at' => null,
        ];
    }

    /**
     * @param  array{
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
     * }  $proxy
     */
    private function buildProxyOptions(array $proxy): array
    {
        $proxyUrl = match ($proxy['protocol']) {
            'HTTP' => 'http://'.$proxy['address'],
            'SOCKS4' => 'socks4://'.$proxy['address'],
            'SOCKS5' => 'socks5://'.$proxy['address'],
            default => throw new \InvalidArgumentException('Protocol proxy tidak didukung.'),
        };

        return [
            'http' => $proxyUrl,
            'https' => $proxyUrl,
        ];
    }

    private function extractDetectedIp(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        $origin = $payload['origin'] ?? null;
        $ip = $payload['ip'] ?? null;

        if (is_string($origin) && $origin !== '') {
            return $origin;
        }

        if (is_string($ip) && $ip !== '') {
            return $ip;
        }

        return null;
    }

    private function validProxy(array $proxy, Response $response, float $startedAt): array
    {
        $proxy['status'] = 'Valid';
        $proxy['response_time_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
        $proxy['detected_ip'] = $this->extractDetectedIp($response->json());
        $proxy['error_message'] = null;
        $proxy['last_checked_at'] = now()->format('d M Y H:i:s');

        return $proxy;
    }

    private function invalidProxy(array $proxy, ?string $errorMessage): array
    {
        $proxy['status'] = 'Invalid';
        $proxy['response_time_ms'] = null;
        $proxy['detected_ip'] = null;
        $proxy['error_message'] = $errorMessage ?? 'Unknown validation error';
        $proxy['last_checked_at'] = now()->format('d M Y H:i:s');

        return $proxy;
    }

    private function resultErrorMessage(mixed $result): string
    {
        if ($result instanceof Response) {
            return $this->summarizeError("HTTP {$result->status()}");
        }

        if ($result instanceof Throwable) {
            return $this->summarizeError($result->getMessage());
        }

        return 'Unknown validation error';
    }

    private function summarizeError(string $message): string
    {
        return Str::limit($message, 180);
    }
}
