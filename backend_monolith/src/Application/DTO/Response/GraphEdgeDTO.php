<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

final readonly class GraphEdgeDTO
{
    public function __construct(
        public string $source,
        public string $target,
    ) {
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'target' => $this->target,
        ];
    }
}
