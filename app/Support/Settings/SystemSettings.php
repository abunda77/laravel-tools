<?php

namespace App\Support\Settings;

use App\Models\AppSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SystemSettings
{
    private const CACHE_PREFIX = 'tools.settings.';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $items = [];

        foreach ($this->definitions() as $key => $definition) {
            $items[$key] = $this->get($key);
        }

        return $items;
    }

    public function get(string $key): mixed
    {
        $definition = $this->definition($key);

        if ($this->isSecret($key)) {
            return $this->resolveValue($key, $definition);
        }

        return Cache::remember(
            $this->cacheKey($key),
            now()->addMinutes(10),
            fn () => $this->resolveValue($key, $definition),
        );
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function resolveValue(string $key, array $definition): mixed
    {
        $setting = AppSetting::query()->where('key', $key)->value('value');

        if ($setting === null) {
            return $definition['default'] ?? null;
        }

        if ($this->isSecret($key)) {
            try {
                return Crypt::decryptString($setting);
            } catch (DecryptException) {
                return '';
            }
        }

        return $this->castValue($setting, $definition['default'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value);
        }
    }

    public function put(string $key, mixed $value): void
    {
        $this->definition($key);

        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $this->prepareForStorage($key, $value)],
        );

        Cache::forget($this->cacheKey($key));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return config('tools.settings', []);
    }

    public function isSecret(string $key): bool
    {
        return (bool) ($this->definition($key)['secret'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(string $key): array
    {
        return config("tools.settings.{$key}")
            ?? throw new \InvalidArgumentException("Undefined setting [{$key}].");
    }

    private function castValue(string $value, mixed $default): mixed
    {
        return match (gettype($default)) {
            'integer' => (int) $value,
            'double' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => $value,
        };
    }

    private function prepareForStorage(string $key, mixed $value): string
    {
        if ($this->isSecret($key)) {
            return Crypt::encryptString((string) $value);
        }

        return match (true) {
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    private function cacheKey(string $key): string
    {
        return self::CACHE_PREFIX.$key;
    }
}
