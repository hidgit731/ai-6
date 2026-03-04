<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

class SearchResultItemResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $headline,
        public readonly float $rank,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'headline' => $this->headline,
            'rank' => $this->rank,
        ];
    }
}
