<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int         $id
 * @property string      $name        Identifier unik, e.g. downloader_provider
 * @property string      $label       Nama tampilan, e.g. Downloader Provider
 * @property string|null $description Deskripsi kegunaan
 * @property string|null $raw_value   Raw encrypted value stored in DB (use ->getDecryptedValue() instead)
 * @property bool        $is_active
 */
class ApiKey extends Model
{
    protected $fillable = [
        'name',
        'label',
        'description',
        'value',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Actually stored column in DB is 'value'.
     * Encrypt before saving.
     */
    public function setValueAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['value'] = null;
        } else {
            $this->attributes['value'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt when reading.
     */
    public function getValueAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Find an API key by its unique name.
     */
    public static function findByName(string $name): ?static
    {
        return static::query()->where('name', $name)->first();
    }

    /**
     * Get only the decrypted value by name (or null).
     */
    public static function valueByName(string $name): ?string
    {
        return static::findByName($name)?->value;
    }

    /**
     * Scope active API keys only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
