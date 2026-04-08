<?php

namespace App\Services\ApiKeys;

use App\Models\ApiKey;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiKeyBackupService
{
    private const DIRECTORY = 'api-key-backups';

    private const TYPE = 'laravel-tools.api-keys';

    private const VERSION = 1;

    /**
     * @return array<int, array{filename: string, path: string, size: int, last_modified: \Illuminate\Support\Carbon}>
     */
    public function backups(): array
    {
        return collect(Storage::disk('local')->files(self::DIRECTORY))
            ->filter(fn (string $path): bool => str_ends_with($path, '.json'))
            ->map(fn (string $path): array => [
                'filename' => basename($path),
                'path' => $path,
                'size' => Storage::disk('local')->size($path),
                'last_modified' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($path)),
            ])
            ->sortByDesc('last_modified')
            ->values()
            ->all();
    }

    public function create(): string
    {
        $filename = 'api-key-backup-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.json';
        $path = self::DIRECTORY.'/'.$filename;

        Storage::disk('local')->put($path, $this->payload());

        return $filename;
    }

    public function download(string $filename): StreamedResponse
    {
        $path = $this->pathFromFilename($filename);

        if (! Storage::disk('local')->exists($path)) {
            throw new InvalidArgumentException('File backup tidak ditemukan.');
        }

        return Storage::disk('local')->download($path, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function delete(string $filename): void
    {
        $path = $this->pathFromFilename($filename);

        if (! Storage::disk('local')->exists($path)) {
            throw new InvalidArgumentException('File backup tidak ditemukan.');
        }

        Storage::disk('local')->delete($path);
    }

    public function restoreFromUploadedFile(UploadedFile $file): int
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new InvalidArgumentException('File backup tidak dapat dibaca.');
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $this->restore($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function restore(array $payload): int
    {
        if (($payload['type'] ?? null) !== self::TYPE || ($payload['version'] ?? null) !== self::VERSION) {
            throw new InvalidArgumentException('Format backup API key tidak valid.');
        }

        if (! is_array($payload['api_keys'] ?? null)) {
            throw new InvalidArgumentException('Data API key tidak ditemukan di file backup.');
        }

        return DB::transaction(function () use ($payload): int {
            $restored = 0;

            foreach ($payload['api_keys'] as $apiKey) {
                if (! is_array($apiKey)) {
                    throw new InvalidArgumentException('Data API key di file backup tidak valid.');
                }

                /** @var array<string, mixed> $apiKey */
                $this->validateApiKeyPayload($apiKey);

                ApiKey::query()->updateOrCreate(
                    ['name' => $apiKey['name']],
                    [
                        'label' => $apiKey['label'],
                        'description' => $apiKey['description'] ?? null,
                        'value' => $apiKey['value'] ?? null,
                        'is_active' => (bool) $apiKey['is_active'],
                    ],
                );

                $restored++;
            }

            return $restored;
        });
    }

    private function payload(): string
    {
        $apiKeys = ApiKey::query()
            ->orderBy('name')
            ->get()
            ->map(fn (ApiKey $apiKey): array => [
                'name' => $apiKey->name,
                'label' => $apiKey->label,
                'description' => $apiKey->description,
                'value' => $apiKey->value,
                'is_active' => $apiKey->is_active,
                'created_at' => $apiKey->created_at?->toISOString(),
                'updated_at' => $apiKey->updated_at?->toISOString(),
            ])
            ->values();

        return json_encode([
            'type' => self::TYPE,
            'version' => self::VERSION,
            'exported_at' => now()->toISOString(),
            'count' => $apiKeys->count(),
            'api_keys' => $apiKeys,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function pathFromFilename(string $filename): string
    {
        if (basename($filename) !== $filename || ! preg_match('/^api-key-backup-\d{8}-\d{6}-[a-z0-9]{6}\.json$/', $filename)) {
            throw new InvalidArgumentException('Nama file backup tidak valid.');
        }

        return self::DIRECTORY.'/'.$filename;
    }

    /**
     * @param  array<string, mixed>  $apiKey
     */
    private function validateApiKeyPayload(array $apiKey): void
    {
        if (! is_string($apiKey['name'] ?? null) || ! preg_match('/^[a-z0-9_]+$/', $apiKey['name'])) {
            throw new InvalidArgumentException('Identifier API key di file backup tidak valid.');
        }

        if (! is_string($apiKey['label'] ?? null) || $apiKey['label'] === '') {
            throw new InvalidArgumentException('Label API key di file backup tidak valid.');
        }

        if (array_key_exists('description', $apiKey) && $apiKey['description'] !== null && ! is_string($apiKey['description'])) {
            throw new InvalidArgumentException('Deskripsi API key di file backup tidak valid.');
        }

        if (array_key_exists('value', $apiKey) && $apiKey['value'] !== null && ! is_string($apiKey['value'])) {
            throw new InvalidArgumentException('Value API key di file backup tidak valid.');
        }

        if (! is_bool($apiKey['is_active'] ?? null)) {
            throw new InvalidArgumentException('Status API key di file backup tidak valid.');
        }
    }
}
