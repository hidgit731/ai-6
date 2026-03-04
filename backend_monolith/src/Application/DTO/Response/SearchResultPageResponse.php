<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

class SearchResultPageResponse
{
    /**
     * @param SearchResultItemResponse[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $totalPages,
    ) {
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(static fn (SearchResultItemResponse $item) => $item->toArray(), $this->items),
            'total' => $this->total,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'totalPages' => $this->totalPages,
        ];
    }
}
