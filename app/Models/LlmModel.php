<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LlmModel extends Model
{
    protected $fillable = [
        'provider',
        'name',
        'label',
        'supports_documents',
        'supports_images',
        'supports_web_search',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'supports_documents' => 'boolean',
            'supports_images' => 'boolean',
            'supports_web_search' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
