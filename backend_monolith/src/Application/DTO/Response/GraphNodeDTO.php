<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

final readonly class GraphNodeDTO
{
    public function __construct(
        public string $id,
        public string $title,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
        ];
    }
}
