<?php

namespace App\Services\Ai;

final readonly class ChatResponse
{
    /**
     * @param  array<int, array{title?: string|null, url: string, snippet?: string|null, source_provider?: string|null}>  $citations
     * @param  array<string, mixed>  $usage
     */
    public function __construct(
        public string $content,
        public array $citations = [],
        public array $usage = [],
    ) {}
}
