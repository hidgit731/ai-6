<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

class PaginatedNotesResponse
{
    /**
     * @param NoteListItemResponse[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $totalPages,
    ) {
    }
}
